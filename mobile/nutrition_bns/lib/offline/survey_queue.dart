import 'dart:convert';

import 'package:shared_preferences/shared_preferences.dart';

import '../api/nutrition_api.dart';

class QueuedSurvey {
  QueuedSurvey({
    required this.id,
    required this.payload,
    required this.createdAt,
    this.lastError,
    this.attempts = 0,
    this.conflictCode,
    this.permanentFail = false,
  });

  final String id;
  final Map<String, dynamic> payload;
  final DateTime createdAt;
  String? lastError;
  int attempts;
  String? conflictCode;
  bool permanentFail;

  static const maxAttempts = 8;

  Map<String, dynamic> toJson() => {
        'id': id,
        'payload': payload,
        'created_at': createdAt.toIso8601String(),
        'last_error': lastError,
        'attempts': attempts,
        'conflict_code': conflictCode,
        'permanent_fail': permanentFail,
      };

  factory QueuedSurvey.fromJson(Map<String, dynamic> json) {
    return QueuedSurvey(
      id: json['id']?.toString() ?? '',
      payload: json['payload'] is Map<String, dynamic>
          ? json['payload'] as Map<String, dynamic>
          : Map<String, dynamic>.from(json['payload'] as Map? ?? {}),
      createdAt: DateTime.tryParse(json['created_at']?.toString() ?? '') ?? DateTime.now(),
      lastError: json['last_error']?.toString(),
      attempts: int.tryParse(json['attempts']?.toString() ?? '') ?? 0,
      conflictCode: json['conflict_code']?.toString(),
      permanentFail: json['permanent_fail'] == true,
    );
  }

  String get headLabel {
    final last = payload['head_last_name']?.toString() ?? '';
    final first = payload['head_first_name']?.toString() ?? '';
    final purok = payload['purok_label']?.toString() ?? '';
    final name = '$last, $first'.replaceAll(RegExp(r'^,\s*|,\s*$'), '').trim();
    if (name.isEmpty) return id;
    return purok.isEmpty ? name : '$name · $purok';
  }

  bool get isConflict =>
      conflictCode == 'duplicate' || conflictCode == 'duplicate_override_allowed';

  bool get canOverrideDuplicate => conflictCode == 'duplicate_override_allowed';
}

class SurveyQueue {
  static const _key = 'nutrition_pending_surveys_v1';

  Future<List<QueuedSurvey>> list() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_key);
    if (raw == null || raw.isEmpty) return [];
    try {
      final decoded = jsonDecode(raw);
      if (decoded is! List) return [];
      return decoded
          .whereType<Map>()
          .map((e) => QueuedSurvey.fromJson(Map<String, dynamic>.from(e)))
          .where((e) => e.id.isNotEmpty)
          .toList();
    } catch (_) {
      return [];
    }
  }

  Future<void> _write(List<QueuedSurvey> items) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(
      _key,
      jsonEncode(items.map((e) => e.toJson()).toList()),
    );
  }

  Future<void> enqueue(Map<String, dynamic> payload) async {
    final items = await list();
    final id = 'q_${DateTime.now().millisecondsSinceEpoch}';
    items.add(QueuedSurvey(
      id: id,
      payload: Map<String, dynamic>.from(payload),
      createdAt: DateTime.now(),
    ));
    await _write(items);
  }

  Future<void> remove(String id) async {
    final items = await list();
    items.removeWhere((e) => e.id == id);
    await _write(items);
  }

  Future<bool> _pushOne(
    QueuedSurvey item,
    NutritionApi api, {
    bool allowDuplicateOverride = false,
  }) async {
    final payload = Map<String, dynamic>.from(item.payload);
    if (allowDuplicateOverride) {
      payload['allow_duplicate_override'] = '1';
    }
    await api.saveHouseholdSurvey(payload);
    return true;
  }

  void _markFailure(QueuedSurvey item, ApiException e) {
    item.attempts += 1;
    item.lastError = e.message;
    item.conflictCode = e.status == 409 ? (e.code ?? 'duplicate') : null;
    if (item.attempts >= QueuedSurvey.maxAttempts && !item.isConflict) {
      item.permanentFail = true;
      item.lastError = '${e.message} (stopped after ${QueuedSurvey.maxAttempts} tries)';
    }
  }

  /// Sync one queued item by id. Returns true if removed from queue.
  Future<bool> retryOne(
    String id,
    NutritionApi api, {
    bool allowDuplicateOverride = false,
  }) async {
    final items = await list();
    final idx = items.indexWhere((e) => e.id == id);
    if (idx < 0) return false;
    final item = items[idx];
    if (item.permanentFail && !allowDuplicateOverride) {
      throw ApiException(item.lastError ?? 'This item failed permanently. Delete or ask an admin.');
    }
    try {
      await _pushOne(item, api, allowDuplicateOverride: allowDuplicateOverride);
      items.removeAt(idx);
      await _write(items);
      return true;
    } on ApiException catch (e) {
      _markFailure(item, e);
      items[idx] = item;
      await _write(items);
      rethrow;
    } catch (e) {
      item.attempts += 1;
      item.lastError = e.toString();
      items[idx] = item;
      await _write(items);
      rethrow;
    }
  }

  /// Returns (synced, failed).
  Future<(int synced, int failed)> syncAll(NutritionApi api) async {
    final items = await list();
    if (items.isEmpty) return (0, 0);
    var synced = 0;
    var failed = 0;
    final remaining = <QueuedSurvey>[];
    for (final item in items) {
      if (item.permanentFail) {
        failed++;
        remaining.add(item);
        continue;
      }
      try {
        await _pushOne(item, api);
        synced++;
      } on ApiException catch (e) {
        failed++;
        _markFailure(item, e);
        remaining.add(item);
        if (e.isUnauthorized) break;
      } catch (e) {
        failed++;
        item.attempts += 1;
        item.lastError = e.toString();
        remaining.add(item);
      }
    }
    await _write(remaining);
    return (synced, failed);
  }
}
