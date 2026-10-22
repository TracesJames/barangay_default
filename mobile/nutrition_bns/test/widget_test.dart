import 'package:flutter_test/flutter_test.dart';
import 'package:nutrition_bns/main.dart';

void main() {
  testWidgets('App boots to login or loading', (tester) async {
    await tester.pumpWidget(const NutritionBnsApp());
    await tester.pump();
    expect(find.byType(NutritionBnsApp), findsOneWidget);
  });
}
