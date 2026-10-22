import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'api/nutrition_api.dart';

class Session {
  Session(this.api);

  final NutritionApi api;
  /// Fired after the stored token is rejected (revoked or expired).
  VoidCallback? onEnded;
  String? token;
  Map<String, dynamic>? user;
  String? activeBarangayId;
  String? activeBarangayName;
  List<dynamic> barangays = [];
  List<dynamic> puroks = [];

  static const _tokenKey = 'nutrition_api_token';
  static const _barangayKeyPrefix = 'nutrition_last_barangay_';

  Future<void> load() async {
    final prefs = await SharedPreferences.getInstance();
    token = prefs.getString(_tokenKey);
    if (token != null && token!.isNotEmpty) {
      api.setToken(token);
      try {
        final me = await api.me();
        _applyMe(me);
        await ensureActiveBarangay();
      } catch (_) {
        await clear();
      }
    }
  }

  Future<void> persistToken(String value) async {
    token = value;
    api.setToken(value);
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_tokenKey, value);
  }

  /// Drop a dead session and return the app to the login screen.
  Future<void> expire() async {
    await clear();
    onEnded?.call();
  }

  Future<bool> expireIfUnauthorized(Object error) async {
    if (error is ApiException && error.isUnauthorized) {
      await expire();
      return true;
    }
    return false;
  }

  Future<void> clear() async {
    token = null;
    user = null;
    activeBarangayId = null;
    activeBarangayName = null;
    barangays = [];
    puroks = [];
    api.setToken(null);
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_tokenKey);
  }

  bool get isLoggedIn => token != null && token!.isNotEmpty;

  bool get mustChangePassword => user?['must_change_password'] == true;

  void clearMustChangePasswordFlag() {
    if (user == null) return;
    user = Map<String, dynamic>.from(user!);
    user!['must_change_password'] = false;
  }

  void _applyMe(Map<String, dynamic> me) {
    user = me['user'] is Map<String, dynamic>
        ? me['user'] as Map<String, dynamic>
        : null;
    activeBarangayId = me['active_barangay_id']?.toString();
    barangays = me['barangays'] is List ? me['barangays'] as List : [];
    puroks = me['puroks'] is List ? me['puroks'] as List : [];
    activeBarangayName = _nameForBarangay(activeBarangayId);
  }

  String? _nameForBarangay(String? id) {
    if (id == null || id.isEmpty) return null;
    for (final b in barangays) {
      if (b is Map && b['id']?.toString() == id) {
        return b['name']?.toString() ?? b['barangay_name']?.toString();
      }
    }
    return null;
  }

  Future<void> login(String username, String password, {bool forceLogin = false}) async {
    final body = await api.login(username, password, forceLogin: forceLogin);
    final t = body['token']?.toString() ?? '';
    if (t.isEmpty) {
      throw ApiException('Login succeeded but no token was returned.');
    }
    await persistToken(t);
    user = body['user'] is Map<String, dynamic>
        ? body['user'] as Map<String, dynamic>
        : null;
    activeBarangayId = body['active_barangay_id']?.toString();
    barangays = body['barangays'] is List ? body['barangays'] as List : [];
    puroks = body['puroks'] is List ? body['puroks'] as List : [];
    activeBarangayName = _nameForBarangay(activeBarangayId);
    await ensureActiveBarangay();
  }

  /// Picks the only assigned barangay, or the last one this account used.
  Future<void> ensureActiveBarangay() async {
    if (activeBarangayId != null && activeBarangayId!.isNotEmpty) {
      await _rememberBarangay(activeBarangayId!);
      return;
    }
    if (barangays.isEmpty) {
      try {
        barangays = await api.barangays();
      } catch (_) {}
    }
    String id = '';
    if (barangays.length == 1) {
      final first = barangays.first;
      id = first is Map ? first['id']?.toString() ?? '' : '';
    } else {
      final saved = await _rememberedBarangay();
      if (saved != null && _barangayKnown(saved)) id = saved;
    }
    if (id.isEmpty) return;
    await selectBarangay(id);
  }

  bool _barangayKnown(String id) {
    for (final b in barangays) {
      if (b is Map && b['id']?.toString() == id) return true;
    }
    return false;
  }

  String? get _userId => user?['id']?.toString();

  Future<void> _rememberBarangay(String id) async {
    final uid = _userId;
    if (uid == null || uid.isEmpty || id.isEmpty) return;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('$_barangayKeyPrefix$uid', id);
  }

  Future<String?> _rememberedBarangay() async {
    final uid = _userId;
    if (uid == null || uid.isEmpty) return null;
    final prefs = await SharedPreferences.getInstance();
    final id = prefs.getString('$_barangayKeyPrefix$uid');
    if (id == null || id.isEmpty) return null;
    return id;
  }

  Future<void> selectBarangay(String barangayId) async {
    final body = await api.setContext(barangayId: barangayId);
    activeBarangayId = body['active_barangay_id']?.toString() ?? barangayId;
    puroks = body['puroks'] is List
        ? body['puroks'] as List
        : await api.puroks(barangayId: barangayId);
    activeBarangayName = body['barangay_name']?.toString() ??
        _nameForBarangay(activeBarangayId);
    await _rememberBarangay(activeBarangayId ?? barangayId);
  }

  Future<void> logout() async {
    try {
      await api.logout();
    } finally {
      await clear();
    }
  }

  String get displayName {
    final u = user;
    if (u == null) return 'User';
    final first = u['first_name']?.toString() ?? '';
    final last = u['last_name']?.toString() ?? '';
    final full = '$first $last'.trim();
    if (full.isNotEmpty) return full;
    return u['username']?.toString() ?? 'User';
  }
}
