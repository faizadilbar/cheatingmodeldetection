import 'dart:async';
import 'dart:convert';
import 'dart:typed_data';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import '../models/session.dart';
import '../models/alarm.dart';
import '../services/api_service.dart';

class LiveStudentScreen extends StatefulWidget {
  final ExamSession session;
  final String token;

  const LiveStudentScreen({
    super.key,
    required this.session,
    required this.token,
  });

  @override
  State<LiveStudentScreen> createState() => _LiveStudentScreenState();
}

class _LiveStudentScreenState extends State<LiveStudentScreen>
    with SingleTickerProviderStateMixin {
  Timer? _refreshTimer;
  Timer? _frameTimer;
  bool _loading = true;
  bool _isConnected = false;
  String _lastUpdated = '';

  // ── Camera frame ──────────────────────────────────────────────────────────
  Uint8List? _currentFrame;
  bool _isFrameLoading = false;
  String? _frameError;
  int _frameFetchCount = 0;
  int _frameFetchFailCount = 0;

  // ── Animation ─────────────────────────────────────────────────────────────
  late AnimationController _scannerController;

  // ── State ──────────────────────────────────────────────────────────────────
  double _riskScore = 0.0;
  double _maxRisk = 0.0;
  int _totalAlarms = 0;
  int _blinkCount = 0;
  int _gazeAwayCount = 0;
  int _headTurnCount = 0;
  int _noFaceCount = 0;
  int _multiFaceCount = 0;
  String _sessionStatus = 'active';
  String _alarmLevel = 'low';
  Map<String, dynamic> _flags = {};

  double _faceCenterX = 0.5;
  double _faceCenterY = 0.5;

  // ── Server URL ──────────────────────────────────────────────────────────
  // For emulator: 10.0.2.2, for physical device: your computer's IP
  String get _serverUrl => 'http://10.0.2.2:5000';

  @override
  void initState() {
    super.initState();
    _riskScore = widget.session.avgRiskScore;
    _maxRisk = widget.session.maxRiskScore;
    _blinkCount = widget.session.totalBlinks;
    _gazeAwayCount = widget.session.gazeAwayCount;
    _headTurnCount = widget.session.headTurnCount;
    _noFaceCount = widget.session.noFaceCount;
    _multiFaceCount = widget.session.multipleFaceCount;
    _sessionStatus = widget.session.status;
    _totalAlarms = widget.session.alarmCount ?? 0;

    _scannerController = AnimationController(
      duration: const Duration(seconds: 4),
      vsync: this,
    )..repeat(reverse: true);

    _loadLiveDataFromFlask();
    _startLiveRefresh();
    _startFrameFetching();
  }

  @override
  void dispose() {
    _refreshTimer?.cancel();
    _frameTimer?.cancel();
    _scannerController.dispose();
    super.dispose();
  }

  // ── Polling ─────────────────────────────────────────────────────────────

  void _startLiveRefresh() {
    // Fetch metrics from Flask every 1.5 seconds
    _refreshTimer = Timer.periodic(const Duration(milliseconds: 1500), (timer) {
      _loadLiveDataFromFlask();
    });
  }

  void _startFrameFetching() {
    // Fetch frame every 2 seconds
    _frameTimer = Timer.periodic(const Duration(seconds: 2), (timer) {
      _fetchStudentFrame();
    });

    // Also fetch immediately
    _fetchStudentFrame();
  }

  // ── Fetch live metrics from Flask server ──────────────────────────────

  Future<void> _loadLiveDataFromFlask() async {
    try {
      final url = '$_serverUrl/metrics';
      debugPrint('📊 Fetching metrics from: $url');

      final response = await http
          .get(
            Uri.parse(url),
          )
          .timeout(const Duration(seconds: 3));

      debugPrint('📊 Metrics response status: ${response.statusCode}');

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (mounted) {
          setState(() {
            _lastUpdated = _getCurrentTime();

            // ── Update all metrics from Flask ──
            _riskScore = (data['avg_risk_score'] ?? data['risk_score'] ?? 0.0)
                .toDouble();
            _maxRisk =
                (data['max_risk_score'] ?? data['max_risk'] ?? 0.0).toDouble();
            _blinkCount =
                (data['total_blinks'] ?? data['blink_count'] ?? 0).toInt();
            _gazeAwayCount = (data['gaze_away_count'] ?? 0).toInt();
            _headTurnCount = (data['head_turn_count'] ?? 0).toInt();
            _noFaceCount = (data['no_face_count'] ?? 0).toInt();
            _multiFaceCount = (data['multiple_face_count'] ?? 0).toInt();
            _sessionStatus = data['status']?.toString() ?? 'active';
            _alarmLevel = data['alarm_level']?.toString() ?? 'low';
            _totalAlarms =
                (data['alarm_count'] ?? data['total_alarms'] ?? 0).toInt();

            // ── Flags ──
            final rawFlags = data['flags'];
            if (rawFlags is Map) {
              _flags = Map<String, dynamic>.from(rawFlags);
            }

            // ── Face position ──
            _faceCenterX = (data['face_center_x'] ?? 0.5).toDouble();
            _faceCenterY = (data['face_center_y'] ?? 0.5).toDouble();

            // ── Connection status ──
            _isConnected =
                data['status'] == 'active' || _sessionStatus == 'active';
            _loading = false;

            debugPrint(
                '📊 Flask Metrics - Risk: $_riskScore%, Alarms: $_totalAlarms, Blinks: $_blinkCount');
          });
        }
      } else {
        debugPrint(
            '⚠️ Failed to fetch metrics from Flask: ${response.statusCode}');
      }
    } catch (e) {
      debugPrint('❌ Flask metrics fetch error: $e');
    }
  }

  Future<void> _fetchStudentFrame() async {
    if (_isFrameLoading) return;

    _isFrameLoading = true;
    try {
      final url = '$_serverUrl/get-frame/${widget.session.studentId}';
      debugPrint('📸 Fetching frame from: $url');

      final response = await http.get(
        Uri.parse(url),
        headers: {
          'Accept': 'application/json',
        },
      ).timeout(const Duration(seconds: 3));

      debugPrint('📸 Response status: ${response.statusCode}');

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['status'] == true && data['frame'] != null) {
          final frameBase64 = data['frame'];
          final frameBytes = base64Decode(frameBase64);
          setState(() {
            _currentFrame = frameBytes;
            _frameError = null;
            _frameFetchCount++;
            _frameFetchFailCount = 0;
            _isConnected = true;
          });
          debugPrint('✅ Frame received: ${frameBytes.length} bytes');
        } else {
          setState(() {
            _frameError = data['message'] ?? 'No frame available';
            _frameFetchFailCount++;
            if (_frameFetchFailCount > 5) {
              _isConnected = false;
            }
          });
          debugPrint('⚠️ Frame fetch failed: ${data['message']}');
        }
      } else {
        setState(() {
          _frameError = 'Server error: ${response.statusCode}';
          _frameFetchFailCount++;
          if (_frameFetchFailCount > 5) {
            _isConnected = false;
          }
        });
        debugPrint('❌ Frame fetch HTTP error: ${response.statusCode}');
      }
    } catch (e) {
      debugPrint('❌ Frame fetch error: $e');
      setState(() {
        _frameError = 'Connecting to student...';
        _frameFetchFailCount++;
        if (_frameFetchFailCount > 5) {
          _isConnected = false;
        }
      });
    } finally {
      _isFrameLoading = false;
    }
  }

  String _getCurrentTime() {
    final now = DateTime.now();
    return '${now.hour.toString().padLeft(2, '0')}:${now.minute.toString().padLeft(2, '0')}:${now.second.toString().padLeft(2, '0')}';
  }

  Color _getRiskColor(double risk) {
    if (risk >= 70) return Colors.redAccent;
    if (risk >= 40) return Colors.amberAccent;
    return const Color(0xFF00E676);
  }

  @override
  Widget build(BuildContext context) {
    final bool hasViolation = _alarmLevel == 'high' ||
        _alarmLevel == 'medium' ||
        _riskScore >= 40 ||
        _flags['no_face'] == true ||
        _flags['multiple_faces'] == true ||
        _flags['gaze_away'] == true ||
        _flags['head_turn'] == true;

    final List<String> activeViolations = [];
    if (_flags['no_face'] == true) {
      activeViolations.add('No Face Detected');
    }
    if (_flags['multiple_faces'] == true) {
      activeViolations.add('Multiple Faces');
    }
    if (_flags['gaze_away'] == true) {
      activeViolations.add('Looking Away');
    }
    if (_flags['head_turn'] == true) {
      activeViolations.add('Head Pose Violation');
    }

    final Color statusColor = _sessionStatus == 'calibrating'
        ? Colors.blueAccent
        : hasViolation
            ? _getRiskColor(_riskScore)
            : const Color(0xFF00E676);

    String statusMessage = "Active Monitoring • Focus Secured";
    if (_sessionStatus == 'calibrating') {
      statusMessage = "Calibrating Head Pose... Please wait";
    } else if (activeViolations.isNotEmpty) {
      statusMessage = activeViolations.join(' | ');
    } else if (_riskScore >= 40) {
      statusMessage = "⚠️ Warning: High Risk Detected";
    }

    return Theme(
      data: ThemeData.dark().copyWith(
        scaffoldBackgroundColor: const Color(0xFF0D0E15),
        colorScheme: const ColorScheme.dark(
          primary: Colors.blueAccent,
          secondary: Colors.pinkAccent,
          surface: Color(0xFF161722),
        ),
      ),
      child: Scaffold(
        appBar: AppBar(
          backgroundColor: const Color(0xFF0D0E15),
          elevation: 0,
          title: const Text(
            'Live Student',
            style: TextStyle(
                fontWeight: FontWeight.bold, fontSize: 18, letterSpacing: 0.5),
          ),
          leading: IconButton(
            icon: const Icon(Icons.arrow_back_ios_new, size: 20),
            onPressed: () => Navigator.pop(context),
          ),
          actions: [
            Padding(
              padding: const EdgeInsets.only(right: 16.0),
              child: Center(
                child: Text(
                  'Updated: $_lastUpdated',
                  style: const TextStyle(color: Colors.white54, fontSize: 11),
                ),
              ),
            ),
          ],
        ),
        body: _loading
            ? const Center(
                child: CircularProgressIndicator(color: Colors.blueAccent),
              )
            : SafeArea(
                child: Padding(
                  padding: const EdgeInsets.symmetric(
                      horizontal: 16.0, vertical: 8.0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      // ── Header ──
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                widget.session.studentName,
                                style: const TextStyle(
                                  fontSize: 20,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                              const SizedBox(height: 2),
                              Text(
                                "ID: ${widget.session.studentId} • Code: ${widget.session.quizCode.isNotEmpty ? widget.session.quizCode : 'N/A'}",
                                style: const TextStyle(
                                  fontSize: 12,
                                  color: Colors.white54,
                                ),
                              ),
                            ],
                          ),
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
                                  style: TextStyle(
                                    fontSize: 10,
                                    fontWeight: FontWeight.bold,
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
                      const SizedBox(height: 16),

                      // ── Camera Feed ──
                      Expanded(
                        flex: 5,
                        child: Container(
                          decoration: BoxDecoration(
                            color: Colors.white.withOpacity(0.02),
                            borderRadius: BorderRadius.circular(24),
                            border: Border.all(
                                color: Colors.white.withOpacity(0.08)),
                          ),
                          child: ClipRRect(
                            borderRadius: BorderRadius.circular(24),
                            child: Stack(
                              fit: StackFit.expand,
                              children: [
                                // ── Student Camera Feed ──
                                if (_currentFrame != null)
                                  Image.memory(
                                    _currentFrame!,
                                    fit: BoxFit.cover,
                                    gaplessPlayback: true,
                                    errorBuilder: (context, error, stackTrace) {
                                      return _buildCameraFallback();
                                    },
                                  )
                                else
                                  _buildCameraFallback(),

                                // ── Status Banner ──
                                Positioned(
                                  top: 16,
                                  left: 16,
                                  right: 16,
                                  child: Container(
                                    padding: const EdgeInsets.symmetric(
                                        vertical: 10, horizontal: 14),
                                    decoration: BoxDecoration(
                                      color: statusColor.withOpacity(0.15),
                                      borderRadius: BorderRadius.circular(12),
                                      border: Border.all(
                                          color: statusColor.withOpacity(0.3)),
                                    ),
                                    child: Row(
                                      children: [
                                        Icon(
                                          _sessionStatus == "calibrating"
                                              ? Icons.sync
                                              : hasViolation
                                                  ? Icons.warning_amber_rounded
                                                  : Icons.check_circle_outline,
                                          color: statusColor,
                                          size: 20,
                                        ),
                                        const SizedBox(width: 10),
                                        Expanded(
                                          child: Text(
                                            statusMessage,
                                            style: const TextStyle(
                                              color: Colors.white,
                                              fontSize: 13,
                                              fontWeight: FontWeight.w600,
                                            ),
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                ),

                                // ── Face Guide ──
                                Center(
                                  child: Container(
                                    width: 190,
                                    height: 250,
                                    decoration: BoxDecoration(
                                      borderRadius: BorderRadius.circular(120),
                                      border: Border.all(
                                        color: statusColor.withOpacity(0.5),
                                        width: 2.0,
                                      ),
                                    ),
                                    child: Align(
                                      alignment: Alignment.bottomCenter,
                                      child: Container(
                                        margin:
                                            const EdgeInsets.only(bottom: 12),
                                        padding: const EdgeInsets.symmetric(
                                            horizontal: 10, vertical: 4),
                                        decoration: BoxDecoration(
                                          color: Colors.black87,
                                          borderRadius:
                                              BorderRadius.circular(12),
                                        ),
                                        child: const Text(
                                          "AI Tracking Locked",
                                          style: TextStyle(
                                            fontSize: 10,
                                            fontWeight: FontWeight.bold,
                                            color: Colors.white70,
                                          ),
                                        ),
                                      ),
                                    ),
                                  ),
                                ),

                                // ── Face Tracking Indicator ──
                                TweenAnimationBuilder<Alignment>(
                                  tween: AlignmentTween(
                                    begin: Alignment.center,
                                    end: Alignment(
                                      (1.0 - 2 * _faceCenterX).clamp(-1.0, 1.0),
                                      (2 * _faceCenterY - 1.0).clamp(-1.0, 1.0),
                                    ),
                                  ),
                                  duration: const Duration(milliseconds: 700),
                                  curve: Curves.easeOutCubic,
                                  builder: (context, alignment, child) {
                                    return Align(
                                      alignment: alignment,
                                      child: Container(
                                        width: 36,
                                        height: 36,
                                        decoration: BoxDecoration(
                                          shape: BoxShape.circle,
                                          color: statusColor.withOpacity(0.12),
                                          border: Border.all(
                                            color: statusColor.withOpacity(0.9),
                                            width: 2.0,
                                          ),
                                          boxShadow: [
                                            BoxShadow(
                                              color:
                                                  statusColor.withOpacity(0.35),
                                              blurRadius: 10,
                                              spreadRadius: 2,
                                            ),
                                          ],
                                        ),
                                        child: Icon(
                                          Icons.face_outlined,
                                          color: statusColor,
                                          size: 18,
                                        ),
                                      ),
                                    );
                                  },
                                ),

                                // ── Alarm Banner ──
                                if (hasViolation || _totalAlarms > 0)
                                  Positioned(
                                    bottom: 16,
                                    left: 16,
                                    right: 16,
                                    child: Container(
                                      padding: const EdgeInsets.symmetric(
                                          horizontal: 14, vertical: 10),
                                      decoration: BoxDecoration(
                                        color: Colors.red.shade900
                                            .withOpacity(0.9),
                                        borderRadius: BorderRadius.circular(12),
                                        border: Border.all(
                                            color: Colors.redAccent,
                                            width: 1.5),
                                      ),
                                      child: Column(
                                        crossAxisAlignment:
                                            CrossAxisAlignment.start,
                                        mainAxisSize: MainAxisSize.min,
                                        children: [
                                          Row(
                                            children: [
                                              const Icon(Icons.warning_rounded,
                                                  color: Colors.yellowAccent,
                                                  size: 20),
                                              const SizedBox(width: 8),
                                              const Expanded(
                                                child: Text(
                                                  "⚠️ CHEATING ALARM TRIGGERED",
                                                  style: TextStyle(
                                                    color: Colors.yellowAccent,
                                                    fontSize: 10,
                                                    fontWeight: FontWeight.bold,
                                                  ),
                                                ),
                                              ),
                                            ],
                                          ),
                                          const SizedBox(height: 4),
                                          if (activeViolations.isNotEmpty) ...[
                                            Wrap(
                                              spacing: 6,
                                              runSpacing: 4,
                                              children: activeViolations
                                                  .map((v) => Container(
                                                        padding:
                                                            const EdgeInsets
                                                                .symmetric(
                                                                horizontal: 8,
                                                                vertical: 3),
                                                        decoration:
                                                            BoxDecoration(
                                                          color: Colors
                                                              .redAccent
                                                              .withOpacity(0.2),
                                                          borderRadius:
                                                              BorderRadius
                                                                  .circular(6),
                                                          border: Border.all(
                                                              color: Colors
                                                                  .redAccent
                                                                  .withOpacity(
                                                                      0.5)),
                                                        ),
                                                        child: Text(
                                                          v,
                                                          style:
                                                              const TextStyle(
                                                            color: Color(
                                                                0xFFFCA5A5),
                                                            fontSize: 10,
                                                            fontWeight:
                                                                FontWeight.w600,
                                                          ),
                                                        ),
                                                      ))
                                                  .toList(),
                                            ),
                                          ] else ...[
                                            const Text(
                                              "EXAM INTEGRITY BREACHED",
                                              style: TextStyle(
                                                color: Colors.white,
                                                fontSize: 12,
                                                fontWeight: FontWeight.w700,
                                              ),
                                            ),
                                          ],
                                        ],
                                      ),
                                    ),
                                  ),
                              ],
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(height: 16),

                      // ── Metrics Row ──
                      Expanded(
                        flex: 4,
                        child: Row(
                          children: [
                            // Risk Gauge
                            Expanded(
                              child: Container(
                                decoration: BoxDecoration(
                                  color: Colors.white.withOpacity(0.02),
                                  borderRadius: BorderRadius.circular(20),
                                  border: Border.all(
                                      color: Colors.white.withOpacity(0.08)),
                                ),
                                child: Padding(
                                  padding: const EdgeInsets.all(12.0),
                                  child: Column(
                                    mainAxisAlignment: MainAxisAlignment.center,
                                    children: [
                                      Stack(
                                        alignment: Alignment.center,
                                        children: [
                                          SizedBox(
                                            width: 85,
                                            height: 85,
                                            child: CircularProgressIndicator(
                                              value: (_riskScore / 100.0)
                                                  .clamp(0.0, 1.0),
                                              strokeWidth: 8,
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
                                                style: const TextStyle(
                                                  fontSize: 22,
                                                  fontWeight: FontWeight.w800,
                                                ),
                                              ),
                                              const Text(
                                                "CURRENT RISK",
                                                style: TextStyle(
                                                  fontSize: 8,
                                                  fontWeight: FontWeight.bold,
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
                            const SizedBox(width: 12),
                            // Mini Stats
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
                                  const SizedBox(height: 8),
                                  Expanded(
                                    child: _buildMiniStatCard(
                                      title: "MAX RISK RECORDED",
                                      value: "${_maxRisk.toStringAsFixed(0)}%",
                                      icon: Icons.trending_up,
                                      color: Colors.purpleAccent,
                                    ),
                                  ),
                                  const SizedBox(height: 8),
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
                      const SizedBox(height: 8),

                      // ── Behaviour Stats ──
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
                          const SizedBox(width: 8),
                          Expanded(
                            child: _buildMiniStatCard(
                              title: "HEAD TURNS",
                              value: "$_headTurnCount",
                              icon: Icons.screen_rotation_outlined,
                              color: const Color(0xFF26C6DA),
                            ),
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: _buildMiniStatCard(
                              title: "NO FACE",
                              value: "$_noFaceCount",
                              icon: Icons.face_retouching_off_outlined,
                              color: const Color(0xFFEF5350),
                            ),
                          ),
                          const SizedBox(width: 8),
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

                      // ── Alarm Summary ──
                      if (_totalAlarms > 0) ...[
                        const SizedBox(height: 12),
                        const Text(
                          "ALARM SUMMARY",
                          style: TextStyle(
                              fontSize: 10,
                              fontWeight: FontWeight.bold,
                              color: Colors.white54),
                        ),
                        const SizedBox(height: 6),
                        Container(
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: Colors.white.withOpacity(0.01),
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(
                                color: Colors.white.withOpacity(0.04)),
                          ),
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.spaceAround,
                            children: [
                              _buildAlarmStat(
                                label: "Total",
                                value: "$_totalAlarms",
                                color: Colors.orange,
                              ),
                              _buildAlarmStat(
                                label: "No Face",
                                value: "$_noFaceCount",
                                color: Colors.red,
                              ),
                              _buildAlarmStat(
                                label: "Head Turn",
                                value: "$_headTurnCount",
                                color: Colors.purple,
                              ),
                              _buildAlarmStat(
                                label: "Gaze Away",
                                value: "$_gazeAwayCount",
                                color: Colors.amber,
                              ),
                              _buildAlarmStat(
                                label: "Multi Face",
                                value: "$_multiFaceCount",
                                color: Colors.pink,
                              ),
                            ],
                          ),
                        ),
                      ],
                    ],
                  ),
                ),
              ),
      ),
    );
  }

  Widget _buildAlarmStat({
    required String label,
    required String value,
    required Color color,
  }) {
    return Column(
      children: [
        Text(
          value,
          style: TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.bold,
            color: color,
          ),
        ),
        Text(
          label,
          style: const TextStyle(
            fontSize: 8,
            color: Colors.white38,
          ),
        ),
      ],
    );
  }

  Widget _buildCameraFallback() {
    return Container(
      color: Colors.black.withOpacity(0.3),
      child: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              _isFrameLoading
                  ? Icons.hourglass_empty
                  : Icons.videocam_off_outlined,
              color: Colors.white38,
              size: 48,
            ),
            const SizedBox(height: 12),
            Text(
              _frameError ?? 'Waiting for student camera...',
              style: const TextStyle(
                color: Colors.white38,
                fontSize: 14,
              ),
              textAlign: TextAlign.center,
            ),
            if (_isFrameLoading) ...[
              const SizedBox(height: 12),
              const CircularProgressIndicator(
                strokeWidth: 2,
                color: Colors.white38,
              ),
            ],
            if (_frameFetchCount > 0) ...[
              const SizedBox(height: 8),
              Text(
                'Frames received: $_frameFetchCount',
                style: const TextStyle(
                  color: Colors.white24,
                  fontSize: 10,
                ),
              ),
            ],
          ],
        ),
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
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
      decoration: BoxDecoration(
        color: Colors.white.withOpacity(0.02),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.white.withOpacity(0.06)),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(4),
            decoration: BoxDecoration(
              color: color.withOpacity(0.1),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Icon(icon, color: color, size: 16),
          ),
          const SizedBox(width: 8),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Text(
                  title,
                  style: const TextStyle(
                    fontSize: 8,
                    fontWeight: FontWeight.bold,
                    color: Colors.white30,
                  ),
                  overflow: TextOverflow.ellipsis,
                ),
                Text(
                  value,
                  style: const TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w800,
                  ),
                  overflow: TextOverflow.ellipsis,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
