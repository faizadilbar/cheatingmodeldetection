# detectors/blink_counter.py

import time
import config


class BlinkCounter:
    """
    Counts blinks using Eye Aspect Ratio (EAR).

    Two detection modes:
    - update()              : Standard mode — detects open→close→open cycle.
                              Works at high FPS (local webcam).
                              EAR_CONSEC_FRAMES=1 means a single closed frame counts.
    - update_single_frame() : Remote mode — only ONE frame arrives every ~2 seconds.
                              A full open→close→open cycle is rarely captured.
                              This method counts a blink whenever EAR drops below
                              threshold IN THE CURRENT FRAME, with a time-based
                              cooldown to avoid double-counting.
    """

    def __init__(self):
        self._counter        = 0
        self._consec_frames  = 0
        self._eye_closed     = False
        self._blink_timestamps: list[float] = []
        self._session_start  = time.time()
        # Remote-mode cooldown: minimum seconds between blinks
        self._last_remote_blink_time = 0.0
        self._REMOTE_BLINK_COOLDOWN  = 1.0  # 1 second between blinks

    def update(self, ear: float) -> bool:
        """Standard high-FPS blink detection (open→close→open cycle).
        Counts a blink when eye re-opens after being closed for >= EAR_CONSEC_FRAMES."""
        blink_detected = False

        if ear < config.EAR_BLINK_THRESHOLD:
            # Eye is closing — increment consecutive closed-frame counter
            self._consec_frames += 1
            self._eye_closed     = True
        else:
            # Eye opened — check if it was closed long enough (>= 1 frame)
            if self._eye_closed and \
               self._consec_frames >= config.EAR_CONSEC_FRAMES:
                self._counter += 1
                self._blink_timestamps.append(time.time())
                blink_detected = True
            # Reset regardless
            self._consec_frames = 0
            self._eye_closed    = False

        return blink_detected

    def update_single_frame(self, ear: float) -> bool:
        """Remote mode blink detection — one frame every ~2 seconds.
        Counts a blink whenever EAR is below threshold in the current frame,
        subject to a cooldown to avoid counting the same blink twice."""
        blink_detected = False
        now = time.time()

        if ear < config.EAR_BLINK_THRESHOLD:
            if (now - self._last_remote_blink_time) >= self._REMOTE_BLINK_COOLDOWN:
                self._counter += 1
                self._blink_timestamps.append(now)
                self._last_remote_blink_time = now
                blink_detected = True

        return blink_detected

    def blink_rate(self, window_seconds: int = 60) -> float:
        now     = time.time()
        cutoff  = now - window_seconds
        recent  = [t for t in self._blink_timestamps if t > cutoff]
        elapsed = min(now - self._session_start, window_seconds)
        if elapsed < 1:
            return 0.0
        return round(len(recent) / elapsed * 60, 1)

    def is_anomalous(self) -> bool:
        rate = self.blink_rate()
        return (rate < config.NORMAL_BLINK_RATE_MIN or
                rate > config.NORMAL_BLINK_RATE_MAX)

    @property
    def count(self) -> int:
        return self._counter

    @count.setter
    def count(self, value: int):
        """Allows restoring blink count from saved session state."""
        self._counter = value