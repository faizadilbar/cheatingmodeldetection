# camera_service.py
# Responsible only for webcam initialization and frame capture.

import cv2
import config

_cap = None

def initialize_camera():
    """Initializes the webcam using config settings.
    Returns:
        bool: True if camera is opened successfully, False otherwise.
    """
    global _cap
    _cap = cv2.VideoCapture(config.CAMERA_INDEX)
    _cap.set(cv2.CAP_PROP_FRAME_WIDTH, config.FRAME_WIDTH)
    _cap.set(cv2.CAP_PROP_FRAME_HEIGHT, config.FRAME_HEIGHT)
    return _cap.isOpened()

def get_frame():
    """Captures a single frame from the webcam, flips it, and returns it.
    Returns:
        numpy.ndarray or None: The captured frame, or None if reading failed.
    """
    global _cap
    if _cap is None or not _cap.isOpened():
        return None
    ret, frame = _cap.read()
    if not ret:
        return None
    frame = cv2.flip(frame, 1)
    return frame

def release_camera():
    """Releases the camera resources."""
    global _cap
    if _cap is not None:
        _cap.release()
        _cap = None
