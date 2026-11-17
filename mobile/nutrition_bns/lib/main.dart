import 'package:flutter/material.dart';

import 'api/nutrition_api.dart';
import 'config.dart';
import 'session.dart';
import 'screens/change_password_screen.dart';
import 'screens/login_screen.dart';
import 'screens/home_shell.dart';
import 'theme/app_theme.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const NutritionBnsApp());
}

class NutritionBnsApp extends StatefulWidget {
  const NutritionBnsApp({super.key});

  @override
  State<NutritionBnsApp> createState() => _NutritionBnsAppState();
}

class _NutritionBnsAppState extends State<NutritionBnsApp> {
  late final Session session;
  bool _ready = false;
  bool _forceUpdate = false;
  String _updateMessage = '';

  @override
  void initState() {
    super.initState();
    session = Session(NutritionApi());
    session.onEnded = () {
      if (mounted) setState(() {});
    };
    session.load().whenComplete(() async {
      await _checkVersion();
      if (mounted) setState(() => _ready = true);
    });
  }

  Future<void> _checkVersion() async {
    try {
      final cfg = await session.api.appConfig(versionCode: appVersionCode);
      final force = cfg['force_update'] == true || cfg['needs_update'] == true;
      if (force && mounted) {
        setState(() {
          _forceUpdate = true;
          _updateMessage = cfg['update_message']?.toString() ??
              'Please install the latest Nutrition BNS APK.';
        });
      }
    } catch (_) {
      // Offline / LAN down — allow app to open.
    }
  }

  void _refresh() => setState(() {});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Nutrition BNS',
      debugShowCheckedModeBanner: false,
      theme: buildNutritionTheme(),
      home: !_ready
          ? Scaffold(
              backgroundColor: AppColors.fog,
              body: Center(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Image.asset(
                      'assets/brand/seal.png',
                      width: 72,
                      height: 72,
                      errorBuilder: (_, __, ___) => const Icon(
                        Icons.eco_rounded,
                        size: 56,
                        color: AppColors.forest,
                      ),
                    ),
                    const SizedBox(height: 20),
                    const SizedBox(
                      width: 28,
                      height: 28,
                      child: CircularProgressIndicator(strokeWidth: 2.5),
                    ),
                  ],
                ),
              ),
            )
          : _forceUpdate
              ? Scaffold(
                  backgroundColor: AppColors.fog,
                  body: SafeArea(
                    child: Padding(
                      padding: const EdgeInsets.all(24),
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          const Icon(Icons.system_update_rounded, size: 56, color: AppColors.forest),
                          const SizedBox(height: 16),
                          Text(
                            'Update required',
                            style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                                  fontWeight: FontWeight.w800,
                                ),
                          ),
                          const SizedBox(height: 8),
                          Text(
                            _updateMessage,
                            textAlign: TextAlign.center,
                          ),
                          const SizedBox(height: 8),
                          Text(
                            'Installed $appVersionName ($appVersionCode)',
                            style: const TextStyle(color: AppColors.muted),
                          ),
                        ],
                      ),
                    ),
                  ),
                )
          : !session.isLoggedIn
              ? LoginScreen(session: session, onLoggedIn: _refresh)
              : session.mustChangePassword
                  ? ChangePasswordScreen(
                      session: session,
                      required: true,
                      onChanged: _refresh,
                    )
                  : HomeShell(session: session, onChanged: _refresh),
    );
  }
}
