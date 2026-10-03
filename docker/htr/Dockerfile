FROM python:3.11-slim

ENV PYTHONUNBUFFERED=1 PIP_NO_CACHE_DIR=1 PIP_DISABLE_PIP_VERSION_CHECK=1
RUN apt-get update && apt-get install -y --no-install-recommends libvips42 curl && rm -rf /var/lib/apt/lists/*
RUN pip install --extra-index-url https://download.pytorch.org/whl/cpu kraken==5.3.0
RUN mkdir /models && curl -fsSL -o /models/recognition.mlmodel "https://zenodo.org/records/13788177/files/McCATMuS_nfd_nofix_V1.mlmodel?download=1"

WORKDIR /app
COPY server.py .
EXPOSE 8000
HEALTHCHECK --interval=10s --timeout=3s --start-period=60s CMD curl -fs http://localhost:8000/health || exit 1
CMD ["python", "server.py"]
