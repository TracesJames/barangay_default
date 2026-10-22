import 'package:flutter/material.dart';

import '../api/nutrition_api.dart';
import '../session.dart';
import '../theme/app_theme.dart';

class ChangePasswordScreen extends StatefulWidget {
  const ChangePasswordScreen({
    super.key,
    required this.session,
    this.required = false,
    this.onChanged,
  });

  final Session session;
  /// When true, user cannot dismiss until password is changed.
  final bool required;
  final VoidCallback? onChanged;

  @override
  State<ChangePasswordScreen> createState() => _ChangePasswordScreenState();
}

class _ChangePasswordScreenState extends State<ChangePasswordScreen> {
  final _formKey = GlobalKey<FormState>();
  final _currentCtrl = TextEditingController();
  final _newCtrl = TextEditingController();
  final _confirmCtrl = TextEditingController();
  bool _busy = false;
  bool _obscureCurrent = true;
  bool _obscureNew = true;
  bool _obscureConfirm = true;
  String? _error;

  @override
  void dispose() {
    _currentCtrl.dispose();
    _newCtrl.dispose();
    _confirmCtrl.dispose();
    super.dispose();
  }

  String? _validateNew(String? v) {
    final s = v ?? '';
    if (s.length < 8) return 'At least 8 characters';
    if (!RegExp(r'[A-Z]').hasMatch(s) || !RegExp(r'[a-z]').hasMatch(s) || !RegExp(r'[0-9]').hasMatch(s)) {
      return 'Need upper, lower, and a number';
    }
    return null;
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await widget.session.api.changePassword(
        currentPassword: _currentCtrl.text,
        newPassword: _newCtrl.text,
        confirmPassword: _confirmCtrl.text,
      );
      widget.session.clearMustChangePasswordFlag();
      widget.onChanged?.call();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Password updated.')),
      );
      if (widget.required) {
        // Parent (main) rebuilds into HomeShell via onChanged.
        return;
      }
      Navigator.of(context).pop(true);
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } catch (_) {
      setState(() => _error = 'Could not update password. Try again.');
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: !widget.required,
      child: Scaffold(
      appBar: AppBar(
        title: Text(widget.required ? 'Set a new password' : 'Change password'),
        automaticallyImplyLeading: !widget.required,
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.fromLTRB(20, 16, 20, 32),
          children: [
            Text(
              widget.required
                  ? 'Your temporary password must be changed before using the app.'
                  : 'Choose a new password for your Nutrition Portal account. '
                      'Other phones signed in with this account will be signed out.',
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: AppColors.muted),
            ),
            const SizedBox(height: 20),
            TextFormField(
              controller: _currentCtrl,
              obscureText: _obscureCurrent,
              decoration: InputDecoration(
                labelText: 'Current password',
                suffixIcon: IconButton(
                  onPressed: () => setState(() => _obscureCurrent = !_obscureCurrent),
                  icon: Icon(_obscureCurrent ? Icons.visibility_outlined : Icons.visibility_off_outlined),
                ),
              ),
              validator: (v) => (v == null || v.isEmpty) ? 'Required' : null,
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: _newCtrl,
              obscureText: _obscureNew,
              decoration: InputDecoration(
                labelText: 'New password',
                helperText: '8+ chars, upper + lower + number',
                suffixIcon: IconButton(
                  onPressed: () => setState(() => _obscureNew = !_obscureNew),
                  icon: Icon(_obscureNew ? Icons.visibility_outlined : Icons.visibility_off_outlined),
                ),
              ),
              validator: _validateNew,
              onChanged: (_) => setState(() {}),
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: _confirmCtrl,
              obscureText: _obscureConfirm,
              decoration: InputDecoration(
                labelText: 'Confirm new password',
                suffixIcon: IconButton(
                  onPressed: () => setState(() => _obscureConfirm = !_obscureConfirm),
                  icon: Icon(_obscureConfirm ? Icons.visibility_outlined : Icons.visibility_off_outlined),
                ),
              ),
              validator: (v) {
                if (v != _newCtrl.text) return 'Passwords do not match';
                return null;
              },
            ),
            if (_error != null) ...[
              const SizedBox(height: 14),
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Theme.of(context).colorScheme.errorContainer,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Text(
                  _error!,
                  style: TextStyle(color: Theme.of(context).colorScheme.onErrorContainer),
                ),
              ),
            ],
            const SizedBox(height: 24),
            SizedBox(
              width: double.infinity,
              child: FilledButton(
                onPressed: _busy ? null : _submit,
                child: _busy
                    ? const SizedBox(
                        width: 20,
                        height: 20,
                        child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                      )
                    : const Text('Update password'),
              ),
            ),
          ],
        ),
      ),
    ),
    );
  }
}
