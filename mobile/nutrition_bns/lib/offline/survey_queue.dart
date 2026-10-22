import 'dart:convert';

import 'package:shared_preferences/shared_preferences.dart';

import '../api/nutrition_api.dart';

class QueuedSurvey {
  QueuedSurvey({
    required this.id,
    required this.payload,
    required this.createdAt,
    this.lastError,
  });

  final String id;
  final Map<String, dynamic> payload;
  final DateTime createdAt;
  String? lastError;

  Map<String, dynamic> toJson() => {
        'id': id,
        'payload': payload,
        'created_at': createdAt.toIso8601String(),
        'last_error': lastError,
      };

  factory QueuedSurvey.fromJson(Map<String, dynamic> json) {
    return QueuedSurvey(
      id: json['id']?.toString() ?? '',
      payload: json['payload'] is Map<String, dynamic>
          ? json['payload'] as Map<String, dynamic>
          : Map<String, dynamic>.from(json['payload'] as Map? ?? {}),
      createdAt: DateTime.tryParse(json['created_at']?.toString() ?? '') ?? DateTime.now(),
      lastError: json['last_error']?.toString(),
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

  /// Sync one queued item by id. Returns true if removed from queue.
  Future<bool> retryOne(String id, NutritionApi api) async {
    final items = await list();
    final idx = items.indexWhere((e) => e.id == id);
    if (idx < 0) return false;
    final item = items[idx];
    try {
      await api.saveHouseholdSurvey(item.payload);
      items.removeAt(idx);
      await _write(items);
      return true;
    } on ApiException catch (e) {
      item.lastError = e.message;
      items[idx] = item;
      await _write(items);
      rethrow;
    } catch (e) {
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
      try {
        await api.saveHouseholdSurvey(item.payload);
        synced++;
      } on ApiException catch (e) {
        failed++;
        item.lastError = e.message;
        remaining.add(item);
        if (e.isUnauthorized) break;
      } catch (e) {
        failed++;
        item.lastError = e.toString();
        remaining.add(item);
      }
    }
    await _write(remaining);
    return (synced, failed);
  }
}
