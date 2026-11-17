# Nutrition BNS (Flutter MVP)

Mobile client for the Barangay Nutrition Portal API (`/api/v1`).

**App version:** `0.2.0+2` (keep `lib/config.dart` + `api/v1/app-config.php` in sync).

## Setup

Flutter SDK (this PC): `C:\src\flutter` — add `C:\src\flutter\bin` to PATH.

1. Enable **Windows Developer Mode** (Settings → System → For developers) for plugin symlinks.
2. From this folder:

```bash
flutter pub get
flutter run -d chrome --web-port=7357 --dart-define=API_BASE=http://localhost/barangay/api/v1
```

### Android (phone / emulator)

Android SDK on this PC: `E:\Android\Sdk` (Flutter already configured).  
Gradle cache: `E:\gradle-home` (keeps C: free).

1. Accept licenses if needed: `flutter doctor --android-licenses`
2. Emulator → XAMPP on the same PC:

```bash
flutter run --dart-define=API_BASE=http://10.0.2.2/barangay/api/v1
```

Physical phone on the same Wi‑Fi — this PC’s LAN IP is often `192.168.0.85`:

```bash
flutter run --dart-define=API_BASE=http://192.168.0.85/barangay/api/v1
```

Cleartext HTTP is enabled in `AndroidManifest.xml` for local XAMPP only.

### Release APK — LAN field test

```bash
flutter build apk --release --dart-define=API_BASE=http://192.168.0.85/barangay/api/v1
```

Output: `build/app/outputs/flutter-apk/app-release.apk`

### Release APK — production (HTTPS)

```bash
flutter build apk --release --dart-define=API_BASE=https://YOUR_PRODUCTION_HOST/barangay/api/v1
```

Do **not** pass `AUTO_USER` / `AUTO_PASS` on release builds.

**Install for BNS:** copy the APK to phones (USB / shared drive). After install, rotated accounts must change password (`must_change_password`).

**Force update:** raise `min_version_code` in `api/v1/app-config.php` (or set env `NUTRITION_BNS_MIN_VERSION_CODE` / `NUTRITION_BNS_FORCE_UPDATE=1`). Older APKs show an update-required screen.

If C: is low on space, build from a copy under `E:\projects\nutrition_bns` with `TEMP`/`GRADLE_USER_HOME` on E:.

### Debug auto-login (debug builds only)

```bash
flutter run -d chrome --dart-define=API_BASE=http://localhost/barangay/api/v1 --dart-define=AUTO_USER=bns_user --dart-define=AUTO_PASS=secret
```

Auto-login is gated with `kDebugMode` and is ignored in release.

## Features

- Login (Bearer token) with clearer rate-limit / network errors
- Dashboard status chips → children list
- Registered households (search / purok filter; edit when allowed)
- Encode survey — web-parity sections
- Edit survey via Households → Encode (`surveyId`)
- **Incomplete households** list + AppBar badge (`GET /incomplete-households.php`)
- Reports tab
- **Offline queue** with retry / conflict override / delete / attempt limit
- Quiet sync on Encode when queue is pending
- Change password via Account menu
- **Version gate** via `GET /app-config.php`
- **Photo attach** on saved surveys (`POST /attachment.php`, JPEG/PNG/WebP ≤5 MB)
- App icon + splash

### Regenerate branding

```bash
dart run flutter_launcher_icons
dart run flutter_native_splash:create
```

## Ops

- Rotated weak BNS passwords: `C:\xampp\secure\barangay_ops\`
- Survey photos: `C:\xampp\secure\barangay_uploads\nutrition\` (fallback `storage/nutrition_uploads/`)
- Never ship APKs with `AUTO_USER` / `AUTO_PASS`
