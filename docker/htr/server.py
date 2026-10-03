import io
import json
import threading
import unicodedata
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer

from PIL import Image, ImageOps
from kraken import blla, rpred
from kraken.lib import models

MODEL = models.load_any('/models/recognition.mlmodel')
LOCK = threading.Lock()
MAX_BYTES = 10 * 1024 * 1024


def recognize(content):
    image = ImageOps.exif_transpose(Image.open(io.BytesIO(content))).convert('RGB')
    with LOCK:
        segmentation = blla.segment(image)
        records = list(rpred.rpred(network=MODEL, im=image, bounds=segmentation))
    return [
        {
            'text': unicodedata.normalize('NFC', record.prediction).strip(),
            'top': sum(point[1] for point in record.baseline) / len(record.baseline),
        }
        for record in records
        if record.prediction.strip()
    ]


class Handler(BaseHTTPRequestHandler):
    def do_GET(self):
        if self.path == '/health':
            self.answer(200, {'status': 'ok'})
        else:
            self.answer(404, {'error': 'not found'})

    def do_POST(self):
        if self.path != '/recognize':
            self.answer(404, {'error': 'not found'})
            return
        length = int(self.headers.get('Content-Length', 0))
        if length <= 0 or length > MAX_BYTES:
            self.answer(413, {'error': 'image missing or too large'})
            return
        try:
            self.answer(200, {'lines': recognize(self.rfile.read(length))})
        except Exception as error:
            self.answer(422, {'error': str(error)})

    def answer(self, status, payload):
        body = json.dumps(payload, ensure_ascii=False).encode()
        self.send_response(status)
        self.send_header('Content-Type', 'application/json')
        self.send_header('Content-Length', str(len(body)))
        self.end_headers()
        self.wfile.write(body)

    def log_message(self, format, *args):
        pass


ThreadingHTTPServer(('0.0.0.0', 8000), Handler).serve_forever()
