# backend/api_server.py

import sys
import os

# Ensure backend and root directories are in search path to import modules
PARENT_DIR = os.path.dirname(os.path.abspath(__file__))
if PARENT_DIR not in sys.path:
    sys.path.insert(0, PARENT_DIR)
ROOT_DIR = os.path.dirname(PARENT_DIR)
if ROOT_DIR not in sys.path:
    sys.path.insert(0, ROOT_DIR)

from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from backend.websocket.proctor_ws import router as ws_router

app = FastAPI(
    title="AI Proctoring WebSocket Backend",
    description="Real-time distributed proctoring API server using WebSockets",
    version="1.0.0"
)

# Configure CORS
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Include the proctoring WebSocket router
app.include_router(ws_router)

@app.on_event("startup")
async def startup_event():
    import asyncio
    from backend.core.detector_manager import detector_manager
    asyncio.create_task(detector_manager.start_cleanup_loop())

@app.get("/")
def read_root():
    return {
        "status": "online",
        "service": "AI Proctoring API",
        "websocket_endpoint": "/ws/proctor/{session_id}"
    }

if __name__ == "__main__":
    import uvicorn
    # Launch uvicorn server
    print("[INFO] Starting FastAPI server on port 8000...")
    uvicorn.run("backend.api_server:app", host="0.0.0.0", port=8000, reload=True)
