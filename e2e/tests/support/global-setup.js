import fs from 'node:fs';
import { LANE_SESSIONS } from './account.js';

export default function globalSetup() {
    fs.rmSync(LANE_SESSIONS, { recursive: true, force: true });
    fs.mkdirSync(LANE_SESSIONS, { recursive: true });
}
