// This is a basic Flutter widget test for the AI Proctoring Client app.

import 'package:flutter_test/flutter_test.dart';
import 'package:proctor_client/main.dart';

void main() {
  testWidgets('ProctorApp smoke test — login screen renders',
      (WidgetTester tester) async {
    // Build the app and trigger a frame.
    await tester.pumpWidget(const ProctorApp());

    // Verify that the login screen key elements are present.
    expect(find.text('ANTIGRAVITY PROCTOR'), findsOneWidget);
    expect(find.text('Student Credentials'), findsOneWidget);
    expect(find.text('Start Calibration'), findsOneWidget);
  });
}
