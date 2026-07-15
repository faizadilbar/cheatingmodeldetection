import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'dart:typed_data';
import 'dart:ui';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:camera/camera.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

List<CameraDescription> cameras = [];

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  try {
    cameras = await availableCameras();
  } catch (e) {
    debugPrint("Error fetching cameras: $e");
  }
  runApp(const ProctorApp());
}

class ProctorApp extends StatelessWidget {
  const ProctorApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'AI Proctoring Client',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        brightness: Brightness.dark,
        primaryColor: Colors.blueAccent,
        scaffoldBackgroundColor: const Color(0xFF0D0E15),
        textTheme: GoogleFonts.outfitTextTheme(ThemeData.dark().textTheme),
        colorScheme: const ColorScheme.dark(
          primary: Colors.blueAccent,
          secondary: Colors.pinkAccent,
          surface: Color(0xFF161722),
        ),
      ),
      home: const LoginScreen(),
    );
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// LOGIN SCREEN
// ─────────────────────────────────────────────────────────────────────────────

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _studentIdController = TextEditingController();
  final _studentNameController = TextEditingController();
  final _courseNameController = TextEditingController();
  final _quizCodeController = TextEditingController();
  // Default points at Flask server on port 5000
  final _serverUrlController =
      TextEditingController(text: "http://localhost:5000");

  bool _isConnecting = false;

  @override
  void initState() {
    super.initState();
    _loadSavedSettings();
  }

  @override
  void dispose() {
    _studentIdController.dispose();
    _studentNameController.dispose();
    _courseNameController.dispose();
    _quizCodeController.dispose();
    _serverUrlController.dispose();
    super.dispose();
  }

  Future<void> _loadSavedSettings() async {
    final prefs = await SharedPreferences.getInstance();
    setState(() {
      _studentIdController.text = prefs.getString("student_id") ?? "";
      _studentNameController.text = prefs.getString("student_name") ?? "";
      _courseNameController.text = prefs.getString("course_name") ?? "";
      _quizCodeController.text = prefs.getString("quiz_code") ?? "";
      _serverUrlController.text =
          prefs.getString("server_url") ?? "http://10.0.2.2:5000";
    });
  }

  Future<void> _saveSettings() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString("student_id", _studentIdController.text);
    await prefs.setString("student_name", _studentNameController.text);
    await prefs.setString("course_name", _courseNameController.text);
    await prefs.setString("quiz_code", _quizCodeController.text);
    await prefs.setString("server_url", _serverUrlController.text);
  }

  Future<void> _startExam() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isConnecting = true);

    final serverUrl = _serverUrlController.text.trim();

    // 1. Health check to make sure server is reachable
    try {
      final health = await http.get(Uri.parse("$serverUrl/health")).timeout(
            const Duration(seconds: 5),
          );
      if (health.statusCode != 200) {
        _showError("Server returned ${health.statusCode}. Is Flask running?");
        setState(() => _isConnecting = false);
        return;
      }
    } catch (_) {
      _showError(
          "Cannot reach server at $serverUrl\nPlease start: python flask_server.py");
      setState(() => _isConnecting = false);
      return;
    }

    await _saveSettings();

    final studentInfo = {
      "student_id": _studentIdController.text.trim(),
      "student_name": _studentNameController.text.trim(),
      "course_name": _courseNameController.text.trim(),
      "quiz_code": _quizCodeController.text.trim(),
      "server_url": serverUrl,
    };

    if (!mounted) return;
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => DashboardScreen(studentInfo: studentInfo),
      ),
    );

    setState(() => _isConnecting = false);
  }

  void _showError(String msg) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(msg, style: GoogleFonts.outfit()),
        backgroundColor: Colors.redAccent,
        duration: const Duration(seconds: 6),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Stack(
        children: [
          // Background gradient blobs
          Positioned(
            top: -100,
            right: -100,
            child: Container(
              width: 350,
              height: 350,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: Colors.pinkAccent.withOpacity(0.12),
              ),
            ),
          ),
          Positioned(
            bottom: -150,
            left: -150,
            child: Container(
              width: 400,
              height: 400,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: Colors.blueAccent.withOpacity(0.12),
              ),
            ),
          ),
          // Content
          SafeArea(
            child: Center(
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(28.0),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    // Brand logo
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: Colors.white.withOpacity(0.03),
                        shape: BoxShape.circle,
                        border:
                            Border.all(color: Colors.white.withOpacity(0.1)),
                      ),
                      child: const Icon(
                        Icons.shield_outlined,
                        size: 64,
                        color: Colors.blueAccent,
                      ),
                    ),
                    const SizedBox(height: 16),
                    Text(
                      "ANTIGRAVITY PROCTOR",
                      style: GoogleFonts.outfit(
                        fontSize: 28,
                        fontWeight: FontWeight.w800,
                        letterSpacing: 2,
                        color: Colors.white,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      "Next-gen Real-time AI Exam Proctoring",
                      style: GoogleFonts.outfit(
                        fontSize: 14,
                        color: Colors.white38,
                      ),
                    ),
                    const SizedBox(height: 32),
                    // Login card (glassmorphic)
                    Container(
                      decoration: BoxDecoration(
                        color: Colors.white.withOpacity(0.04),
                        borderRadius: BorderRadius.circular(28),
                        border: Border.all(
                          color: Colors.white.withOpacity(0.08),
                          width: 1.5,
                        ),
                      ),
                      child: ClipRRect(
                        borderRadius: BorderRadius.circular(28),
                        child: Container(
                          color: Colors.transparent,
                          child: Padding(
                            padding: const EdgeInsets.all(24.0),
                            child: Form(
                              key: _formKey,
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.stretch,
                                children: [
                                  Text(
                                    "Student Credentials",
                                    style: GoogleFonts.outfit(
                                      fontSize: 18,
                                      fontWeight: FontWeight.w600,
                                      color: Colors.white70,
                                    ),
                                  ),
                                  const SizedBox(height: 20),
                                  _buildTextField(
                                    controller: _studentNameController,
                                    label: "Full Name",
                                    icon: Icons.person_outline,
                                  ),
                                  const SizedBox(height: 16),
                                  _buildTextField(
                                    controller: _studentIdController,
                                    label: "Roll No / Student ID",
                                    icon: Icons.badge_outlined,
                                  ),
                                  const SizedBox(height: 16),
                                  _buildTextField(
                                    controller: _courseNameController,
                                    label: "Course Name",
                                    icon: Icons.school_outlined,
                                  ),
                                  const SizedBox(height: 16),
                                  _buildTextField(
                                    controller: _quizCodeController,
                                    label: "Quiz Code",
                                    icon: Icons.app_registration_outlined,
                                  ),
                                  const SizedBox(height: 16),
                                  _buildTextField(
                                    controller: _serverUrlController,
                                    label: "Flask Server URL (port 5000)",
                                    icon: Icons.dns_outlined,
                                  ),
                                  const SizedBox(height: 28),
                                  ElevatedButton(
                                    onPressed:
                                        _isConnecting ? null : _startExam,
                                    style: ElevatedButton.styleFrom(
                                      backgroundColor: Colors.blueAccent,
                                      foregroundColor: Colors.white,
                                      padding: const EdgeInsets.symmetric(
                                          vertical: 16),
                                      shape: RoundedRectangleBorder(
                                        borderRadius: BorderRadius.circular(16),
                                      ),
                                      elevation: 8,
                                      shadowColor:
                                          Colors.blueAccent.withOpacity(0.4),
                                    ),
                                    child: _isConnecting
                                        ? const SizedBox(
                                            height: 20,
                                            width: 20,
                                            child: CircularProgressIndicator(
                                              strokeWidth: 2,
                                              color: Colors.white,
                                            ),
                                          )
                                        : Text(
                                            "Start Calibration",
                                            style: GoogleFonts.outfit(
                                              fontSize: 16,
                                              fontWeight: FontWeight.bold,
                                              letterSpacing: 1,
                                            ),
                                          ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildTextField({
    required TextEditingController controller,
    required String label,
    required IconData icon,
  }) {
    return TextFormField(
      controller: controller,
      validator: (val) => val == null || val.isEmpty ? "Field required" : null,
      decoration: InputDecoration(
        labelText: label,
        labelStyle: GoogleFonts.outfit(color: Colors.white54, fontSize: 14),
        prefixIcon: Icon(icon, color: Colors.white54, size: 20),
        filled: true,
        fillColor: Colors.white.withOpacity(0.03),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: BorderSide(color: Colors.white.withOpacity(0.08)),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: const BorderSide(color: Colors.blueAccent, width: 2),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: const BorderSide(color: Colors.redAccent, width: 1.5),
        ),
        focusedErrorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: const BorderSide(color: Colors.redAccent, width: 2),
        ),
        contentPadding: const EdgeInsets.symmetric(vertical: 16),
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// DASHBOARD SCREEN
// ─────────────────────────────────────────────────────────────────────────────

class DashboardScreen extends StatefulWidget {
  final Map<String, String> studentInfo;
  const DashboardScreen({super.key, required this.studentInfo});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  // ── Camera ──────────────────────────────────────────────────────────────
  CameraController? _cameraController;
  bool _isCameraInitialized = false;

  // ── Connection state ─────────────────────────────────────────────────────
  bool _isConnected = false;
  // ignore: unused_field — retained to track server-acknowledged start; no longer gates the End button
  bool _isSessionStarted = false;
  bool _isSessionEnded = false;
  bool _isStoppingSession = false;

  // ── Polling ──────────────────────────────────────────────────────────────
  Timer? _pollTimer;
  // Reduced from 500ms to 1 s — cuts main-thread wake-ups in half and
  // prevents the Dart event loop from being saturated by overlapping HTTP calls.
  static const Duration _pollInterval = Duration(milliseconds: 1000);

  // ── Proctoring metrics (updated by polling /metrics) ─────────────────────
  double _riskScore = 0.0;
  double _maxRisk = 0.0;
  int _totalAlarms = 0;
  int _lastAlarmCount = 0;
  DateTime? _lastStudentAlarmTime;
  // ignore: unused_field — kept for potential re-enable of multi-beat alarm pattern
  Timer? _studentAudioAlertTimer;
  bool _showVisualAlarmOverlay = false;
  String _alarmViolationTitle = "";
  int _blinkCount = 0;
  String _alarmLevel = "calibrating";
  String _statusMessage = "Connecting to proctor server...";
  // ignore: unused_field — stored for session report, not rendered directly
  String _lastAlarmType = "NONE";
  // ignore: unused_field — tracks calibration state for status message logic
  bool _isCalibrated = false;

  // ── Cumulative violation counters ─────────────────────────────────────────
  int _gazeAwayCount = 0;
  int _headTurnCount = 0;
  int _noFaceCount = 0;
  int _multiFaceCount = 0;

  Map<String, dynamic> _flags = {
    "gaze_away": false,
    "head_turn": false,
    "multiple_faces": false,
    "no_face": false,
  };

  double _faceCenterX = 0.5;
  double _faceCenterY = 0.5;

  String _cameraStatusMessage = "Initializing camera...";

  void _triggerStudentSideAlarm(String violationType, String severity) {
    if (!mounted || _isSessionEnded) return;
    final now = DateTime.now();
    if (_lastStudentAlarmTime != null &&
        now.difference(_lastStudentAlarmTime!).inSeconds < 3) {
      return;
    }
    _lastStudentAlarmTime = now;

    setState(() {
      _showVisualAlarmOverlay = true;
      _alarmViolationTitle = violationType.replaceAll('_', ' ');
    });

    // Single alert + vibration — the rapid 350 ms sub-timer was firing
    // SystemSound/HapticFeedback repeatedly on the main thread and
    // contributing to the ANR under camera load.
    try {
      SystemSound.play(SystemSoundType.alert);
      HapticFeedback.mediumImpact();
    } catch (e) {
      debugPrint('Audio alert error: $e');
    }

    Future.delayed(const Duration(seconds: 4), () {
      if (mounted) {
        setState(() {
          _showVisualAlarmOverlay = false;
        });
      }
    });
  }

  // ── Helpers ───────────────────────────────────────────────────────────────
  String get _baseUrl =>
      widget.studentInfo["server_url"] ?? "http://10.0.2.2:5000";

  http.Client? _httpClient;

  @override
  void initState() {
    super.initState();
    _httpClient = http.Client();
    _initCamera();
    _startExamOnServer();
  }

  // ── Camera ────────────────────────────────────────────────────────────────

  Future<void> _initCamera() async {
    setState(() {
      _cameraStatusMessage = "Detecting cameras...";
    });

    if (cameras.isEmpty) {
      try {
        cameras = await availableCameras();
      } catch (e) {
        setState(() {
          _cameraStatusMessage = "Error fetching cameras: $e";
        });
        debugPrint("Error fetching cameras: $e");
        return;
      }
    }

    if (cameras.isEmpty) {
      setState(() {
        _cameraStatusMessage = "No camera found on this device.";
      });
      return;
    }

    setState(() {
      _cameraStatusMessage = "Initializing camera controller...";
    });

    CameraDescription? frontCam;
    for (final cam in cameras) {
      if (cam.lensDirection == CameraLensDirection.front) {
        frontCam = cam;
        break;
      }
    }
    frontCam ??= cameras.first;

    _cameraController = CameraController(
      frontCam,
      ResolutionPreset.medium,
      enableAudio: false,
    );

    try {
      await _cameraController!.initialize();
      if (mounted) {
        setState(() {
          _isCameraInitialized = true;
          _cameraStatusMessage = "Camera active";
        });
        _startFrameCaptureLoop();
      }
    } catch (e) {
      debugPrint("Camera init error: $e");
      if (mounted) {
        setState(() {
          _cameraStatusMessage = "Camera initialization failed: $e";
        });
      }
    }
  }

  Timer? _frameTimer;
  // Guards against overlapping captures: if a previous upload is still
  // in-flight when the next tick fires, we skip that tick instead of
  // stacking async work on the main isolate.
  bool _isFrameUploadInProgress = false;

  void _startFrameCaptureLoop() {
    _frameTimer?.cancel();
    // 3 s instead of 2 s — the extra second gives the previous upload
    // time to complete and avoids stacking concurrent HTTP requests.
    _frameTimer = Timer.periodic(const Duration(seconds: 3), (timer) async {
      if (_isSessionEnded ||
          _cameraController == null ||
          !_cameraController!.value.isInitialized ||
          _isFrameUploadInProgress) return;

      _isFrameUploadInProgress = true;
      try {
        final file = await _cameraController!.takePicture();
        final bytes = await file.readAsBytes();
        // Compress on a background isolate so the main thread is free.
        final compressed = await compute(_compressJpegInBackground, bytes);
        await _uploadFrame(compressed);
        final ioFile = File(file.path);
        if (await ioFile.exists()) await ioFile.delete();
      } catch (e) {
        debugPrint('Error capturing/uploading frame: $e');
      } finally {
        _isFrameUploadInProgress = false;
      }
    });
  }

  /// Top-level function required by [compute] so it can be sent to a
  /// background isolate.  Simply returns the bytes as-is — `takePicture()`
  /// already produces a real JPEG; no manual pixel loop is needed.
  static Uint8List _compressJpegInBackground(Uint8List rawBytes) => rawBytes;

  Future<void> _uploadFrame(Uint8List bytes) async {
    if (_isSessionEnded) return;
    try {
      final uri = Uri.parse("$_baseUrl/upload-frame");
      final request = http.MultipartRequest("POST", uri);

      request.files.add(
        http.MultipartFile.fromBytes(
          "frame",
          bytes,
          filename: "frame.jpg",
        ),
      );

      final streamedResponse =
          await request.send().timeout(const Duration(seconds: 3));
      final response = await http.Response.fromStream(streamedResponse);

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data["status"] == true && mounted) {
          debugPrint("Frame uploaded and processed successfully");
          if (data["result"] != null) {
            _updateProctorMetrics(data["result"]);
          }
        }
      } else {
        debugPrint("Failed to upload frame: ${response.statusCode}");
      }
    } catch (e) {
      debugPrint("Error uploading frame: $e");
    }
  }

  // ── Start exam via HTTP POST /start-exam ──────────────────────────────────

  Future<void> _startExamOnServer() async {
    setState(() => _statusMessage = "Starting exam on server...");

    final now = DateTime.now();
    final startTimeStr =
        "${now.hour.toString().padLeft(2, '0')}:${now.minute.toString().padLeft(2, '0')}";
    final examDateStr =
        "${now.year}-${now.month.toString().padLeft(2, '0')}-${now.day.toString().padLeft(2, '0')}";

    final payload = {
      "student_id": widget.studentInfo["student_id"],
      "student_name": widget.studentInfo["student_name"],
      "course_name": widget.studentInfo["course_name"],
      "quiz_code": widget.studentInfo["quiz_code"],
      "start_time": startTimeStr,
      "exam_date": examDateStr,
    };

    try {
      final response = await _httpClient!
          .post(
            Uri.parse("$_baseUrl/start-exam"),
            headers: {"Content-Type": "application/json"},
            body: jsonEncode(payload),
          )
          .timeout(const Duration(seconds: 30));

      final data = jsonDecode(response.body);

      if (response.statusCode == 200 && data["status"] == true) {
        if (mounted) {
          setState(() {
            _isConnected = true;
            _isSessionStarted = true;
            _statusMessage = "Calibrating Head Pose... Please look straight";
          });
        }
        _startPolling();
      } else {
        final msg = data["message"] ?? "Server error ${response.statusCode}";
        if (mounted) {
          setState(() {
            _isConnected = false;
            _statusMessage = "Server error: $msg";
          });
        }
      }
    } catch (e) {
      debugPrint("start-exam error: $e");
      if (mounted) {
        setState(() {
          _isConnected = false;
          _statusMessage = "Cannot reach server: $e";
        });
      }
    }
  }

  // ── Poll /metrics every 500 ms ────────────────────────────────────────────

  void _startPolling() {
    _pollTimer?.cancel();
    _pollTimer = Timer.periodic(_pollInterval, (_) => _fetchMetrics());
  }

  Future<void> _fetchMetrics() async {
    if (_isSessionEnded) return;

    try {
      final response = await _httpClient!
          .get(Uri.parse("$_baseUrl/metrics"))
          .timeout(const Duration(seconds: 3));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body) as Map<String, dynamic>;
        _updateProctorMetrics(data);
      }
    } catch (_) {
      // Silently skip — network hiccup; keep last values shown
    }
  }

  // ── Update UI from polled metrics ─────────────────────────────────────────

  void _updateProctorMetrics(Map<String, dynamic> data) {
    if (!mounted || _isSessionEnded) return;
    final int newTotalAlarms = data["total_alarms"] ?? data["total_count"] ?? data["alarm_count"] ?? 0;
    final String currentLevel = data["alarm_level"] ?? "calibrating";
    final String lastViol = data["last_alarm_type"] ?? data["violation_type"] ?? "CHEATING DETECTED";
    final bool playAlarmSignal = data["play_alarm"] == true;

    if (newTotalAlarms > _lastAlarmCount ||
        (playAlarmSignal &&
            (currentLevel == "high" || currentLevel == "medium"))) {
      _lastAlarmCount = newTotalAlarms;
      _triggerStudentSideAlarm(lastViol, currentLevel);
    }

    setState(() {
      _riskScore = (data["risk_score"] ?? data["avg_risk_score"] ?? 0.0).toDouble();
      _maxRisk = (data["max_risk"] ?? data["max_risk_score"] ?? 0.0).toDouble();
      _totalAlarms = newTotalAlarms;
      _blinkCount = data["blink_count"] ?? data["total_blinks"] ?? 0;
      _lastAlarmType = lastViol;
      _alarmLevel = currentLevel;
      _flags = Map<String, dynamic>.from(data["flags"] ?? _flags);
      _faceCenterX = (data["face_center_x"] ?? 0.5).toDouble();
      _faceCenterY = (data["face_center_y"] ?? 0.5).toDouble();
      _gazeAwayCount = data["gaze_away_count"] ?? _gazeAwayCount;
      _headTurnCount = data["head_turn_count"] ?? _headTurnCount;
      _noFaceCount = data["no_face_count"] ?? _noFaceCount;
      _multiFaceCount = data["multiple_face_count"] ?? _multiFaceCount;

      if (_alarmLevel == "calibrating") {
        _isCalibrated = false;
        _statusMessage = "Calibrating Head Pose... Please look straight";
      } else {
        _isCalibrated = true;
        _isConnected = data["status"] == "active";

        final List<String> warnings = [];
        if (_flags["no_face"] == true) {
          warnings.add("No Face Detected");
        } else if (_flags["multiple_faces"] == true) {
          warnings.add("Multiple Faces Detected");
        } else {
          if (_flags["gaze_away"] == true) warnings.add("Looking Away");
          if (_flags["head_turn"] == true) warnings.add("Head Pose Violation");
        }

        _statusMessage = warnings.isNotEmpty
            ? warnings.join(" | ")
            : "Active Monitoring • Focus Secured";
      }
    });
  }

  // ── End exam ──────────────────────────────────────────────────────────────

  void _endExam() {
    if (_isStoppingSession) return;
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text("End Session?"),
        content: const Text(
            "Are you sure you want to finish the exam and compile report data?"),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text("Cancel"),
          ),
          ElevatedButton(
            onPressed: () {
              Navigator.pop(ctx);
              _cleanupAndExit();
            },
            style: ElevatedButton.styleFrom(backgroundColor: Colors.redAccent),
            child: const Text("End Session"),
          ),
        ],
      ),
    );
  }

  Future<void> _cleanupAndExit() async {
    if (_isSessionEnded) return;
    if (mounted) setState(() => _isStoppingSession = true);
    _isSessionEnded = true;

    // Cancel timers immediately to stop metric polling and frame uploads
    _pollTimer?.cancel();
    _frameTimer?.cancel();

    // Show status while sending final report to server
    if (mounted) {
      setState(() => _statusMessage = "Sending report to server...");
    }

    // Await the stop-exam call so the server has time to finalize the session
    // report, upload it to the API, and sync all data. We use a generous
    // 15 s timeout — the server's end() method uploads a text file and calls
    // multiple API endpoints. Navigator.pop() is called *after* this completes
    // (or times out) so the user's session is always finalized.
    try {
      final response = await http.post(
        Uri.parse("$_baseUrl/stop-exam"),
        headers: {"Content-Type": "application/json"},
      ).timeout(const Duration(seconds: 15));
      debugPrint(
          "[EndExam] stop-exam response: ${response.statusCode} — ${response.body}");
    } catch (e) {
      debugPrint("[EndExam] stop-exam failed (still proceeding): $e");
    }

    // Dispose camera controller before navigating away
    try {
      if (_cameraController != null) {
        await _cameraController!.dispose();
      }
    } catch (e) {
      debugPrint("[EndExam] Error disposing camera: $e");
    }

    // Pop back to the login screen
    if (mounted) Navigator.pop(context);
  }

  @override
  void dispose() {
    _isSessionEnded = true;
    _pollTimer?.cancel();
    _frameTimer?.cancel();
    _cameraController?.dispose();
    _httpClient?.close();
    super.dispose();
  }

  // ── Colour helpers ────────────────────────────────────────────────────────

  Color _getRiskColor(double risk) {
    if (risk >= 70) return Colors.redAccent;
    if (risk >= 40) return Colors.amberAccent;
    return const Color(0xFF00E676);
  }

  // ── Build ─────────────────────────────────────────────────────────────────

  @override
  Widget build(BuildContext context) {
    final bool hasViolation = _flags["no_face"] == true ||
        _flags["multiple_faces"] == true ||
        _flags["gaze_away"] == true ||
        _flags["head_turn"] == true;

    final Color statusColor = _alarmLevel == "calibrating"
        ? Colors.blueAccent
        : hasViolation
            ? _getRiskColor(_riskScore)
            : const Color(0xFF00E676);

    return Scaffold(
      body: Stack(
        children: [
          // Background blobs
          Positioned(
            top: -150,
            left: -150,
            child: Container(
              width: 450,
              height: 450,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: Colors.blueAccent.withOpacity(0.08),
              ),
            ),
          ),
          Positioned(
            bottom: -100,
            right: -100,
            child: Container(
              width: 350,
              height: 350,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: Colors.pinkAccent.withOpacity(0.08),
              ),
            ),
          ),
          SafeArea(
            child: Padding(
              padding:
                  const EdgeInsets.symmetric(horizontal: 20.0, vertical: 12.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  // ── Header ─────────────────────────────────────────────
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            widget.studentInfo["student_name"]!,
                            style: GoogleFonts.outfit(
                              fontSize: 20,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          Text(
                            "ID: ${widget.studentInfo['student_id']} • ${widget.studentInfo['quiz_code']}",
                            style: GoogleFonts.outfit(
                              fontSize: 13,
                              color: Colors.white54,
                            ),
                          ),
                        ],
                      ),
                      // Connection pill
                      Container(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 12, vertical: 6),
                        decoration: BoxDecoration(
                          color: _isConnected
                              ? const Color(0xFF00C853).withOpacity(0.1)
                              : Colors.red.withOpacity(0.1),
                          borderRadius: BorderRadius.circular(20),
                          border: Border.all(
                            color: _isConnected
                                ? const Color(0xFF00E676).withOpacity(0.3)
                                : Colors.redAccent.withOpacity(0.3),
                          ),
                        ),
                        child: Row(
                          children: [
                            Container(
                              width: 8,
                              height: 8,
                              decoration: BoxDecoration(
                                shape: BoxShape.circle,
                                color: _isConnected
                                    ? const Color(0xFF00E676)
                                    : Colors.redAccent,
                              ),
                            ),
                            const SizedBox(width: 6),
                            Text(
                              _isConnected ? "CONNECTED" : "OFFLINE",
                              style: GoogleFonts.outfit(
                                fontSize: 10,
                                fontWeight: FontWeight.w700,
                                color: _isConnected
                                    ? const Color(0xFF00E676)
                                    : Colors.redAccent,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 20),

                  // ── Camera Preview ──────────────────────────────────────
                  Expanded(
                    flex: 4,
                    child: Container(
                      decoration: BoxDecoration(
                        color: Colors.white.withOpacity(0.03),
                        borderRadius: BorderRadius.circular(24),
                        border:
                            Border.all(color: Colors.white.withOpacity(0.08)),
                      ),
                      child: ClipRRect(
                        borderRadius: BorderRadius.circular(24),
                        child: Stack(
                          fit: StackFit.expand,
                          children: [
                            if (_isCameraInitialized)
                              LayoutBuilder(
                                builder: (context, constraints) {
                                  final size = constraints.biggest;
                                  double cameraAspect =
                                      _cameraController!.value.aspectRatio;
                                  if (cameraAspect > 1.0)
                                    cameraAspect = 1.0 / cameraAspect;

                                  final containerAspect =
                                      size.width / size.height;
                                  double scale = 1.0;
                                  if (cameraAspect < containerAspect) {
                                    scale = containerAspect / cameraAspect;
                                  } else {
                                    scale = cameraAspect / containerAspect;
                                  }

                                   return ClipRect(
                                    child: TweenAnimationBuilder<Alignment>(
                                      tween: AlignmentTween(
                                        begin: Alignment.center,
                                        end: Alignment(
                                          (1.0 - 2 * _faceCenterX).clamp(-1.0, 1.0),
                                          (2 * _faceCenterY - 1.0).clamp(-1.0, 1.0),
                                        ),
                                      ),
                                      duration: const Duration(milliseconds: 800),
                                      curve: Curves.easeOutCubic,
                                      builder: (context, animAlignment, child) {
                                        return Transform.scale(
                                          scale: scale,
                                          alignment: animAlignment,
                                          child: child,
                                        );
                                      },
                                      child: Center(
                                        child: AspectRatio(
                                          aspectRatio: cameraAspect,
                                          child: Transform.scale(
                                            scaleX: -1.0,
                                            child: CameraPreview(
                                                _cameraController!),
                                          ),
                                        ),
                                      ),
                                    ),
                                  );
                                },
                              )
                            else
                              Center(
                                child: Column(
                                  mainAxisAlignment: MainAxisAlignment.center,
                                  children: [
                                    const CircularProgressIndicator(),
                                    const SizedBox(height: 16),
                                    Padding(
                                      padding: const EdgeInsets.symmetric(
                                          horizontal: 24),
                                      child: Text(
                                        _cameraStatusMessage,
                                        textAlign: TextAlign.center,
                                        style: GoogleFonts.outfit(
                                          color: Colors.white70,
                                          fontSize: 14,
                                        ),
                                      ),
                                    ),
                                  ],
                                ),
                              ),

                            // Face position guide overlay
                            if (_isCameraInitialized)
                              Center(
                                child: Container(
                                  width: 210,
                                  height: 270,
                                  decoration: BoxDecoration(
                                    borderRadius: BorderRadius.circular(120),
                                    border: Border.all(
                                      color: statusColor.withOpacity(0.6),
                                      width: 2.5,
                                    ),
                                    boxShadow: [
                                      BoxShadow(
                                        color: statusColor.withOpacity(0.15),
                                        blurRadius: 15,
                                        spreadRadius: 2,
                                      )
                                    ],
                                  ),
                                  child: Align(
                                    alignment: Alignment.bottomCenter,
                                    child: Container(
                                      margin: const EdgeInsets.only(bottom: 12),
                                      padding: const EdgeInsets.symmetric(
                                          horizontal: 12, vertical: 4),
                                      decoration: BoxDecoration(
                                        color: Colors.black87,
                                        borderRadius: BorderRadius.circular(12),
                                      ),
                                      child: Text(
                                        "Center Face Here",
                                        style: GoogleFonts.outfit(
                                          fontSize: 11,
                                          fontWeight: FontWeight.bold,
                                          color: Colors.white70,
                                        ),
                                      ),
                                    ),
                                  ),
                                ),
                              ),

                            // Status banner overlay
                            Positioned(
                              top: 16,
                              left: 16,
                              right: 16,
                              child: ClipRRect(
                                borderRadius: BorderRadius.circular(16),
                                child: BackdropFilter(
                                  filter:
                                      ImageFilter.blur(sigmaX: 10, sigmaY: 10),
                                  child: Container(
                                    padding: const EdgeInsets.symmetric(
                                        vertical: 12, horizontal: 16),
                                    color: statusColor.withOpacity(0.15),
                                    child: Row(
                                      children: [
                                        Icon(
                                          _alarmLevel == "calibrating"
                                              ? Icons.sync_outlined
                                              : (_statusMessage
                                                          .contains("Secure") ||
                                                      _statusMessage
                                                          .contains("Connect"))
                                                  ? Icons.check_circle_outline
                                                  : Icons.warning_amber_rounded,
                                          color: statusColor,
                                        ),
                                        const SizedBox(width: 12),
                                        Expanded(
                                          child: Text(
                                            _statusMessage,
                                            style: GoogleFonts.outfit(
                                              color: Colors.white,
                                              fontSize: 14,
                                              fontWeight: FontWeight.w600,
                                            ),
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                ),
                              ),
                            ),

                            // Student Visual Alarm Alert Banner
                            if (_showVisualAlarmOverlay ||
                                _alarmLevel == "high" ||
                                _alarmLevel == "medium")
                              Positioned(
                                bottom: 16,
                                left: 16,
                                right: 16,
                                child: ClipRRect(
                                  borderRadius: BorderRadius.circular(16),
                                  child: Container(
                                    padding: const EdgeInsets.symmetric(
                                        horizontal: 16, vertical: 12),
                                    decoration: BoxDecoration(
                                      color:
                                          Colors.red.shade900.withOpacity(0.95),
                                      borderRadius: BorderRadius.circular(16),
                                      border: Border.all(
                                          color: Colors.redAccent, width: 2),
                                      boxShadow: [
                                        BoxShadow(
                                          color:
                                              Colors.redAccent.withOpacity(0.5),
                                          blurRadius: 15,
                                          spreadRadius: 2,
                                        )
                                      ],
                                    ),
                                    child: Row(
                                      children: [
                                        const Icon(Icons.warning_rounded,
                                            color: Colors.yellowAccent,
                                            size: 28),
                                        const SizedBox(width: 10),
                                        Expanded(
                                          child: Column(
                                            crossAxisAlignment:
                                                CrossAxisAlignment.start,
                                            mainAxisSize: MainAxisSize.min,
                                            children: [
                                              Text(
                                                "⚠️ CHEATING ALARM TRIGGERED",
                                                style: GoogleFonts.outfit(
                                                  color: Colors.yellowAccent,
                                                  fontSize: 11,
                                                  fontWeight: FontWeight.bold,
                                                ),
                                              ),
                                              Text(
                                                _alarmViolationTitle.isNotEmpty
                                                    ? _alarmViolationTitle
                                                    : "FOCUS ON EXAM SCREEN",
                                                style: GoogleFonts.outfit(
                                                  color: Colors.white,
                                                  fontSize: 13,
                                                  fontWeight: FontWeight.w700,
                                                ),
                                              ),
                                            ],
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                ),
                              ),
                          ],
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 20),

                  // ── Metrics Cards ───────────────────────────────────────
                  Expanded(
                    flex: 3,
                    child: Row(
                      children: [
                        // Risk gauge
                        Expanded(
                          child: Container(
                            decoration: BoxDecoration(
                              color: Colors.white.withOpacity(0.03),
                              borderRadius: BorderRadius.circular(24),
                              border: Border.all(
                                  color: Colors.white.withOpacity(0.08)),
                            ),
                            child: ClipRRect(
                              borderRadius: BorderRadius.circular(24),
                              child: BackdropFilter(
                                filter:
                                    ImageFilter.blur(sigmaX: 10, sigmaY: 10),
                                child: Padding(
                                  padding: const EdgeInsets.all(16.0),
                                  child: Column(
                                    mainAxisAlignment: MainAxisAlignment.center,
                                    children: [
                                      Stack(
                                        alignment: Alignment.center,
                                        children: [
                                          SizedBox(
                                            width: 110,
                                            height: 110,
                                            child: CircularProgressIndicator(
                                              value: _riskScore / 100.0,
                                              strokeWidth: 10,
                                              backgroundColor: Colors.white
                                                  .withOpacity(0.05),
                                              color: _getRiskColor(_riskScore),
                                            ),
                                          ),
                                          Column(
                                            mainAxisSize: MainAxisSize.min,
                                            children: [
                                              Text(
                                                "${_riskScore.toStringAsFixed(0)}%",
                                                style: GoogleFonts.outfit(
                                                  fontSize: 26,
                                                  fontWeight: FontWeight.w800,
                                                ),
                                              ),
                                              Text(
                                                "CURRENT RISK",
                                                style: GoogleFonts.outfit(
                                                  fontSize: 8,
                                                  fontWeight: FontWeight.w600,
                                                  color: Colors.white54,
                                                ),
                                              ),
                                            ],
                                          ),
                                        ],
                                      ),
                                    ],
                                  ),
                                ),
                              ),
                            ),
                          ),
                        ),
                        const SizedBox(width: 16),
                        // Mini stat cards
                        Expanded(
                          child: Column(
                            children: [
                              Expanded(
                                child: _buildMiniStatCard(
                                  title: "BLINKS",
                                  value: "$_blinkCount",
                                  icon: Icons.remove_red_eye_outlined,
                                  color: Colors.blueAccent,
                                ),
                              ),
                              const SizedBox(height: 12),
                              Expanded(
                                child: _buildMiniStatCard(
                                  title: "MAX RISK RECORDED",
                                  value: "${_maxRisk.toStringAsFixed(0)}%",
                                  icon: Icons.trending_up,
                                  color: Colors.purpleAccent,
                                ),
                              ),
                              const SizedBox(height: 12),
                              Expanded(
                                child: _buildMiniStatCard(
                                  title: "VIOLATION ALARMS",
                                  value: "$_totalAlarms",
                                  icon: Icons.notifications_none_outlined,
                                  color: Colors.redAccent,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 12),

                  // ── Behaviour Stats Row ─────────────────────────────────────
                  Row(
                    children: [
                      Expanded(
                        child: _buildMiniStatCard(
                          title: "GAZE AWAY",
                          value: "$_gazeAwayCount",
                          icon: Icons.visibility_off_outlined,
                          color: const Color(0xFFFFB300),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: _buildMiniStatCard(
                          title: "HEAD TURNS",
                          value: "$_headTurnCount",
                          icon: Icons.screen_rotation_outlined,
                          color: const Color(0xFF26C6DA),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: _buildMiniStatCard(
                          title: "NO FACE",
                          value: "$_noFaceCount",
                          icon: Icons.face_retouching_off_outlined,
                          color: const Color(0xFFEF5350),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: _buildMiniStatCard(
                          title: "MULTI FACE",
                          value: "$_multiFaceCount",
                          icon: Icons.group_outlined,
                          color: const Color(0xFFAB47BC),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 20),

                  // ── End Session Button ──────────────────────────────────
                  ElevatedButton(
                    onPressed:
                        _isSessionEnded || _isStoppingSession ? null : _endExam,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.redAccent.withOpacity(0.12),
                      foregroundColor: Colors.redAccent,
                      surfaceTintColor: Colors.redAccent,
                      padding: const EdgeInsets.symmetric(vertical: 16),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(16),
                        side:
                            const BorderSide(color: Colors.redAccent, width: 1),
                      ),
                      elevation: 0,
                    ),
                    child: _isStoppingSession
                        ? const SizedBox(
                            height: 20,
                            width: 20,
                            child: CircularProgressIndicator(
                              strokeWidth: 2,
                              color: Colors.redAccent,
                            ),
                          )
                        : Text(
                            "End Proctoring Session",
                            style: GoogleFonts.outfit(
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                              letterSpacing: 1.5,
                            ),
                          ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildMiniStatCard({
    required String title,
    required String value,
    required IconData icon,
    required Color color,
  }) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white.withOpacity(0.03),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.white.withOpacity(0.08)),
      ),
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: color.withOpacity(0.1),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(icon, color: color, size: 18),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Text(
                  title,
                  style: GoogleFonts.outfit(
                    fontSize: 8,
                    fontWeight: FontWeight.w600,
                    color: Colors.white54,
                  ),
                ),
                Text(
                  value,
                  style: GoogleFonts.outfit(
                    fontSize: 15,
                    fontWeight: FontWeight.bold,
                    color: Colors.white,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// FRAME STREAMING SERVICE
// ─────────────────────────────────────────────────────────────────────────────

/// Manages camera frame capture, downsampling, JPEG conversion, and queued multipart uploads.
class FrameStreamingService {
  final String serverUrl;
  final String studentId;
  final String quizCode;

  // **Where Frames Are Queued:**
  // This queue buffers processed JPEG byte arrays (Uint8List) in-memory so that
  // transmission latency does not block the camera capture thread or cause UI lags.
  final List<Uint8List> _frameQueue = [];
  bool _isProcessing = false;
  Timer? _senderTimer;

  FrameStreamingService({
    required this.serverUrl,
    required this.studentId,
    required this.quizCode,
  }) {
    _startSenderLoop();
  }

  /// Enqueues a raw CameraImage for asynchronous processing and JPEG compression.
  void enqueueFrame(CameraImage image) {
    Future.microtask(() {
      try {
        final jpegBytes = _compressCameraImageToJpeg(image);
        if (jpegBytes != null) {
          // Limit queue size to 10 to prevent memory pressure under poor connectivity
          if (_frameQueue.length < 10) {
            _frameQueue.add(jpegBytes);
            debugPrint(
                "[FrameStreaming] Enqueued JPEG frame. Queue size: ${_frameQueue.length} (${jpegBytes.length} bytes)");
          } else {
            debugPrint(
                "[FrameStreaming] Queue full, dropping frame to avoid memory pressure");
          }
        }
      } catch (e) {
        debugPrint("[FrameStreaming] Error processing frame: $e");
      }
    });
  }

  /// **Compression & Conversion Layer:**
  /// Downsamples and formats the raw YUV420_888 camera planes into JPEG bytes.
  ///
  /// Processing Pipeline:
  /// 1. Extracts Plane 0 (Y plane / luminance channel) representing the grayscale data.
  /// 2. Downsamples the resolution by a factor of 4 to save CPU, RAM, and network bandwidth.
  /// 3. Converts pixels and wraps them in a standard JPEG SOI/EOI stream wrapper.
  ///
  /// In a production environment, you can replace this with native compression using:
  /// - `flutter_image_compress` (highly optimized native code).
  /// - `image` package (img.encodeJpg).
  Uint8List? _compressCameraImageToJpeg(CameraImage image) {
    try {
      if (image.planes.isEmpty) return null;

      final plane = image.planes[0]; // Y (luminance/grayscale) plane
      final bytes = plane.bytes;
      final width = plane.width ?? image.width;
      final height = plane.height ?? image.height;

      const int downsampleFactor = 4;
      final int newWidth = width ~/ downsampleFactor;
      final int newHeight = height ~/ downsampleFactor;

      // Extract grayscale RGB values from the luminance plane
      final Uint8List rgbBytes = Uint8List(newWidth * newHeight * 3);
      int targetIndex = 0;

      for (int y = 0; y < newHeight; y++) {
        final int sourceRowStart = y * downsampleFactor * width;
        for (int x = 0; x < newWidth; x++) {
          final int sourcePixelIndex = sourceRowStart + (x * downsampleFactor);
          if (sourcePixelIndex < bytes.length) {
            final int yValue = bytes[sourcePixelIndex];

            // Grayscale mapping: R = G = B = Y
            rgbBytes[targetIndex++] = yValue; // Red channel
            rgbBytes[targetIndex++] = yValue; // Green channel
            rgbBytes[targetIndex++] = yValue; // Blue channel
          }
        }
      }

      // Structure as a JPEG stream container
      return _wrapInJpegContainer(rgbBytes);
    } catch (e) {
      debugPrint("[FrameStreaming] Compression error: $e");
      return null;
    }
  }

  /// Wraps the processed pixels in a binary container with JPEG Start of Image (SOI)
  /// and End of Image (EOI) markers to simulate standard JPEG streams.
  Uint8List _wrapInJpegContainer(Uint8List rgbBytes) {
    final builder = BytesBuilder(copy: false);
    builder.add([0xFF, 0xD8]); // SOI (Start of Image) marker
    builder.add(rgbBytes); // Image raw binary payload
    builder.add([0xFF, 0xD9]); // EOI (End of Image) marker
    return builder.takeBytes();
  }

  void _startSenderLoop() {
    // Process queue periodically (throttling transmission to ~3 FPS)
    _senderTimer =
        Timer.periodic(const Duration(milliseconds: 333), (timer) async {
      if (_isProcessing || _frameQueue.isEmpty) return;

      _isProcessing = true;
      final Uint8List jpegBytes = _frameQueue.removeAt(0);

      try {
        await _sendFrameToServer(jpegBytes);
      } catch (e) {
        debugPrint("[FrameStreaming] Failed to send frame: $e");
      } finally {
        _isProcessing = false;
      }
    });
  }

  /// **Where Future API Integration Will Happen:**
  /// Constructs a Multipart HTTP request to upload the JPEG image.
  /// Once a backend streaming endpoint (e.g. `/api/exam-sessions/{session_id}/stream-frame`)
  /// is defined on Railway, you can configure the request here.
  Future<void> _sendFrameToServer(Uint8List jpegBytes) async {
    /*
    final url = Uri.parse("$serverUrl/api/exam-sessions/stream-frame");
    final request = http.MultipartRequest("POST", url);

    // Add session details as text fields
    request.fields["student_id"] = studentId;
    request.fields["quiz_code"] = quizCode;
    request.fields["timestamp"] = DateTime.now().toIso8601String();

    // Attach the compressed JPEG image payload as a file field
    request.files.add(
      http.MultipartFile.fromBytes(
        "frame",
        jpegBytes,
        filename: "frame.jpg",
      ),
    );

    try {
      final streamedResponse = await request.send().timeout(const Duration(seconds: 4));
      final response = await http.Response.fromStream(streamedResponse);
      
      if (response.statusCode == 200) {
        debugPrint("[FrameStreaming] Frame uploaded successfully via multipart upload.");
      } else {
        debugPrint("[FrameStreaming] Multipart upload failed with status: ${response.statusCode}");
      }
    } catch (e) {
      debugPrint("[FrameStreaming] Network error during multipart upload: $e");
    }
    */

    // Simulate standard connection latency
    await Future.delayed(const Duration(milliseconds: 50));
    // debugPrint("[FrameStreaming] Mock multipart upload of JPEG image to $serverUrl (size: ${jpegBytes.length} bytes)");
  }

  void dispose() {
    _senderTimer?.cancel();
    _frameQueue.clear();
    debugPrint("[FrameStreaming] Service disposed.");
  }
}
