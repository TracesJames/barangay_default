import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';

import '../api/nutrition_api.dart';
import '../session.dart';
import '../theme/app_theme.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key, required this.session, required this.onLoggedIn});

  final Session session;
  final VoidCallback onLoggedIn;

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _userCtrl = TextEditingController();
  final _passCtrl = TextEditingController();
  final _formKey = GlobalKey<FormState>();
  bool _busy = false;
  bool _obscure = true;
  String? _error;

  static const _autoUser = String.fromEnvironment('AUTO_USER');
  static const _autoPass = String.fromEnvironment('AUTO_PASS');

  @override
  void initState() {
    super.initState();
    if (kDebugMode && _autoUser.isNotEmpty && _autoPass.isNotEmpty) {
      _userCtrl.text = _autoUser;
      _passCtrl.text = _autoPass;
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) _submit();
      });
    }
  }

  @override
  void dispose() {
    _userCtrl.dispose();
    _passCtrl.dispose();
    super.dispose();
  }

  Future<void> _submit({bool forceLogin = false}) async {
    if (!_formKey.currentState!.validate()) return;
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await widget.session.login(
        _userCtrl.text.trim(),
        _passCtrl.text,
        forceLogin: forceLogin,
      );
      widget.onLoggedIn();
    } on ApiException catch (e) {
      if (e.code == 'already_logged_in' && mounted) {
        setState(() => _busy = false);
        final takeOver = await showDialog<bool>(
          context: context,
          builder: (ctx) => AlertDialog(
            title: const Text('Already logged in'),
            content: const Text(
              'This account is already signed in on another device or browser. '
              'Continue here and sign out the other session?',
            ),
            actions: [
              TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
              FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Continue here')),
            ],
          ),
        );
        if (takeOver == true && mounted) {
          await _submit(forceLogin: true);
        }
        return;
      }
      setState(() => _error = e.message);
    } catch (_) {
      setState(() => _error = 'Could not reach the server. Check API_BASE / Wi‑Fi.');
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [
              Color(0xFF163E1A),
              Color(0xFF1B5E20),
              Color(0xFFE8F5E9),
              Color(0xFFF4F7F4),
            ],
            stops: [0.0, 0.38, 0.62, 1.0],
          ),
        ),
        child: SafeArea(
          child: Center(
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 420),
              child: ListView(
                padding: const EdgeInsets.fromLTRB(24, 28, 24, 32),
                children: [
                  const SizedBox(height: 12),
                  Center(
                    child: Container(
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        color: Colors.white.withValues(alpha: 0.14),
                        borderRadius: BorderRadius.circular(28),
                        border: Border.all(color: Colors.white.withValues(alpha: 0.22)),
                      ),
                      child: Image.asset(
                        'assets/brand/seal.png',
                        width: 88,
                        height: 88,
                        errorBuilder: (_, __, ___) => const Icon(
                          Icons.eco_rounded,
                          size: 72,
                          color: Colors.white,
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 20),
                  Text(
                    'Nutrition Portal',
                    textAlign: TextAlign.center,
                    style: theme.textTheme.headlineMedium?.copyWith(
                      color: Colors.white,
                      fontWeight: FontWeight.w800,
                      letterSpacing: -0.5,
                    ),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    'City of Valencia · BNS field app',
                    textAlign: TextAlign.center,
                    style: theme.textTheme.bodyMedium?.copyWith(
                      color: Colors.white.withValues(alpha: 0.85),
                    ),
                  ),
                  const SizedBox(height: 36),
                  Form(
                    key: _formKey,
                    child: Container(
                      padding: const EdgeInsets.all(20),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(24),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withValues(alpha: 0.08),
                            blurRadius: 28,
                            offset: const Offset(0, 12),
                          ),
                        ],
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          Text(
                            'Sign in',
                            style: theme.textTheme.titleLarge?.copyWith(
                              fontWeight: FontWeight.w800,
                              color: AppColors.ink,
                            ),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            'Use your Nutrition Portal account',
                            style: theme.textTheme.bodySmall?.copyWith(color: AppColors.muted),
                          ),
                          const SizedBox(height: 20),
                          TextFormField(
                            controller: _userCtrl,
                            enabled: !_busy,
                            textInputAction: TextInputAction.next,
                            decoration: const InputDecoration(
                              labelText: 'Username',
                              prefixIcon: Icon(Icons.person_outline_rounded),
                            ),
                            validator: (v) =>
                                (v == null || v.trim().isEmpty) ? 'Username is required' : null,
                          ),
                          const SizedBox(height: 12),
                          TextFormField(
                            controller: _passCtrl,
                            enabled: !_busy,
                            obscureText: _obscure,
                            onFieldSubmitted: (_) => _busy ? null : _submit(),
                            decoration: InputDecoration(
                              labelText: 'Password',
                              prefixIcon: const Icon(Icons.lock_outline_rounded),
                              suffixIcon: IconButton(
                                onPressed: _busy
                                    ? null
                                    : () => setState(() => _obscure = !_obscure),
                                icon: Icon(
                                  _obscure
                                      ? Icons.visibility_outlined
                                      : Icons.visibility_off_outlined,
                                ),
                              ),
                            ),
                            validator: (v) =>
                                (v == null || v.isEmpty) ? 'Password is required' : null,
                          ),
                          if (_error != null) ...[
                            const SizedBox(height: 14),
                            Container(
                              padding: const EdgeInsets.all(12),
                              decoration: BoxDecoration(
                                color: theme.colorScheme.errorContainer.withValues(alpha: 0.55),
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: Text(
                                _error!,
                                style: TextStyle(
                                  color: theme.colorScheme.onErrorContainer,
                                  fontWeight: FontWeight.w500,
                                ),
                              ),
                            ),
                          ],
                          const SizedBox(height: 20),
                          FilledButton(
                            onPressed: _busy ? null : _submit,
                            child: _busy
                                ? const SizedBox(
                                    height: 22,
                                    width: 22,
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2.4,
                                      color: Colors.white,
                                    ),
                                  )
                                : const Text('Sign in'),
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 28),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Image.asset(
                        'assets/brand/nnc.png',
                        height: 36,
                        errorBuilder: (_, __, ___) => const SizedBox.shrink(),
                      ),
                      const SizedBox(width: 16),
                      Image.asset(
                        'assets/brand/lgu.png',
                        height: 36,
                        errorBuilder: (_, __, ___) => const SizedBox.shrink(),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
