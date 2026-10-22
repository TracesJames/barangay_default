# Nutrition BNS (Flutter MVP)

Mobile client for the Barangay Nutrition Portal API (`/api/v1`).

## Setup

Flutter SDK (this PC): `C:\src\flutter` — add `C:\src\flutter\bin` to PATH.

1. Enable **Windows Developer Mode** (Settings → System → For developers) for plugin symlinks.
2. From this folder:

```bash
flutter pub get
flutter run -d chrome --web-port=7357 --dart-define=API_BASE=http://localhost/barangay_default/api/v1
```

### Android (phone / emulator)

Android SDK on this PC: `E:\Android\Sdk` (Flutter already configured).  
Gradle cache: `E:\gradle-home` (keeps C: free).

1. Accept licenses if needed: `flutter doctor --android-licenses`
2. Emulator → XAMPP on the same PC:

```bash
flutter run --dart-define=API_BASE=http://10.0.2.2/barangay_default/api/v1
```

Physical phone on the same Wi‑Fi — this PC’s LAN IP is often `192.168.0.85`:

```bash
flutter run --dart-define=API_BASE=http://192.168.0.85/barangay_default/api/v1
```

Cleartext HTTP is already enabled in `AndroidManifest.xml` for local XAMPP.

**Release APK for BNS field testing** (LAN API) — preferred handoff build:

```bash
flutter build apk --release --dart-define=API_BASE=http://192.168.0.85/barangay_default/api/v1
```

Output: `build/app/outputs/flutter-apk/app-release.apk` (~76 MB)  
Also: `dist/NutritionBNS-release.apk` and `E:\NutritionBNS-release.apk`

Debug APK (~173 MB) is only for local debugging.

After installing, rotated BNS accounts are prompted to set a new password (`must_change_password`). Flag accounts with:

```bash
php scripts/flag_rotated_bns_must_change_password.php
```

If C: is low on space, build from a copy under `E:\projects\nutrition_bns` with `TEMP`/`GRADLE_USER_HOME` on E:.

Release APK:

```bash
flutter build apk --release --dart-define=API_BASE=https://YOUR_PRODUCTION_HOST/barangay_default/api/v1
```

Do **not** pass `AUTO_USER` / `AUTO_PASS` on release builds.

### Debug auto-login (debug builds only)

```bash
flutter run -d chrome --dart-define=API_BASE=http://localhost/barangay_default/api/v1 --dart-define=AUTO_USER=bns_user --dart-define=AUTO_PASS=secret
```

Auto-login is gated with `kDebugMode` and is ignored in release.

## Features

- Login (Bearer token) with clearer rate-limit / network errors
- Dashboard status chips → children list (nutritional statuses only)
- Registered households (search / purok filter; edit when allowed)
- Encode survey — web-parity sections (BSI, FP, complementary, members, Other Relative)
- Edit survey via Households → Encode (`existing_survey_id`)
- Reports tab (summary + consolidated list)
- Offline queue screen (sync / retry / delete) + quiet sync on Encode
- Change password (Account ⋮ menu) via `POST /change-password.php`
- App icon + splash (city seal / forest green)

### Regenerate branding

```bash
dart run flutter_launcher_icons
dart run flutter_native_splash:create
```

## Android SDK (first time)

1. Open **Android Studio** (already installed) and finish the SDK wizard.
2. `flutter doctor --android-licenses`
3. `flutter run --dart-define=API_BASE=http://192.168.0.85/barangay_default/api/v1`

## Ops

- Rotated weak BNS passwords are stored outside the web root: `C:\xampp\secure\barangay_ops\`
- Never ship APKs with `AUTO_USER` / `AUTO_PASS`.
