/// Override at build time:
/// Emulator (HTTP only for local debug):
///   `flutter run --dart-define=API_BASE=http://10.0.2.2/barangay/api/v1 --dart-define=ALLOW_CLEARTEXT=true`
/// Phone LAN HTTPS (recommended):
///   `flutter run --dart-define=API_BASE=https://100.112.56.88/barangay/api/v1`
/// Production:
///   `flutter build apk --release --dart-define=API_BASE=https://HOST/barangay/api/v1`
const String apiBase = String.fromEnvironment(
  'API_BASE',
  defaultValue: 'https://100.112.56.88/barangay/api/v1',
);

/// Debug-only escape hatch for emulator cleartext HTTP. Release builds ignore this.
const bool allowCleartext = bool.fromEnvironment(
  'ALLOW_CLEARTEXT',
  defaultValue: false,
);

/// Keep in sync with pubspec `version:` build number (+N) and api/v1/app-config.php.
const int appVersionCode = int.fromEnvironment('APP_VERSION_CODE', defaultValue: 3);
const String appVersionName = String.fromEnvironment('APP_VERSION_NAME', defaultValue: '0.2.1');
