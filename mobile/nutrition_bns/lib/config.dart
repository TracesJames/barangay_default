/// Override at build time:
/// Emulator: `flutter run --dart-define=API_BASE=http://10.0.2.2/barangay_default/api/v1`
/// Phone (LAN): `flutter run --dart-define=API_BASE=http://192.168.0.85/barangay_default/api/v1`
const String apiBase = String.fromEnvironment(
  'API_BASE',
  defaultValue: 'http://10.0.2.2/barangay_default/api/v1',
);
