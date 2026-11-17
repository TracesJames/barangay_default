/// Override at build time:
/// Emulator: `flutter run --dart-define=API_BASE=http://10.0.2.2/barangay/api/v1`
/// Phone (LAN): `flutter run --dart-define=API_BASE=http://192.168.0.85/barangay/api/v1`
/// Production: `flutter build apk --release --dart-define=API_BASE=https://HOST/barangay/api/v1`
const String apiBase = String.fromEnvironment(
  'API_BASE',
  defaultValue: 'http://10.0.2.2/barangay/api/v1',
);

/// Keep in sync with pubspec `version:` build number (+N) and api/v1/app-config.php.
const int appVersionCode = int.fromEnvironment('APP_VERSION_CODE', defaultValue: 2);
const String appVersionName = String.fromEnvironment('APP_VERSION_NAME', defaultValue: '0.2.0');
