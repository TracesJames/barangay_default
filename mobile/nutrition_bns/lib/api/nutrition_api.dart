import 'dart:async';
import 'dart:convert';

import 'package:http/http.dart' as http;

import '../config.dart';

class ApiException implements Exception {
  ApiException(this.message, {this.status, this.code});
  final String message;
  final int? status;
  final String? code;

  bool get isRateLimited => status == 429 || code == 'rate_limited';
  bool get isUnauthorized => status == 401;
  bool get isNetwork => status == null && code == 'network';

  @override
  String toString() => message;
}

class NutritionApi {
  NutritionApi({http.Client? client}) : _client = client ?? http.Client();

  final http.Client _client;
  String? _token;

  void setToken(String? token) => _token = token;

  Map<String, String> get _headers {
    final h = <String, String>{
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    };
    if (_token != null && _token!.isNotEmpty) {
      h['Authorization'] = 'Bearer $_token';
    }
    return h;
  }

  Uri _uri(String path, [Map<String, String>? query]) {
    final base = apiBase.endsWith('/') ? apiBase.substring(0, apiBase.length - 1) : apiBase;
    final p = path.startsWith('/') ? path : '/$path';
    return Uri.parse('$base$p').replace(queryParameters: query);
  }

  String _friendlyError(Map<String, dynamic> body, int status) {
    final code = body['code']?.toString();
    if (status == 429 || code == 'rate_limited') {
      return 'Too many failed attempts. Try again in about 15 minutes.';
    }
    if (status == 401) {
      return body['error']?.toString() ?? 'Session expired. Please sign in again.';
    }
    if (status == 403) {
      if (code == 'must_change_password') {
        return body['error']?.toString() ??
            'Change your temporary password first (Account → Change password).';
      }
      return body['error']?.toString() ?? 'You do not have permission for this action.';
    }
    if (code == 'no_barangay' || code == 'select_barangay') {
      return body['error']?.toString() ?? 'Select a barangay to continue.';
    }
    if (status == 409) {
      if (code == 'already_logged_in') {
        return body['error']?.toString() ??
            'This account is already logged in on another device.';
      }
      return body['error']?.toString() ??
          'Already registered. This name or record cannot be saved again.';
    }
    if (status == 404) {
      return body['error']?.toString() ?? 'Record not found.';
    }
    if (status >= 500) {
      return 'Server error. Please try again in a moment.';
    }
    return body['error']?.toString() ??
        body['message']?.toString() ??
        'Request failed ($status).';
  }

  Map<String, dynamic> _json(http.Response res) {
    Map<String, dynamic> body;
    try {
      final decoded = jsonDecode(res.body);
      body = decoded is Map<String, dynamic> ? decoded : <String, dynamic>{'data': decoded};
    } catch (_) {
      throw ApiException(
        'Invalid server response (${res.statusCode}). Is XAMPP / API_BASE correct?',
        status: res.statusCode,
      );
    }
    if (res.statusCode >= 400 || body['ok'] == false) {
      throw ApiException(
        _friendlyError(body, res.statusCode),
        status: res.statusCode,
        code: body['code']?.toString(),
      );
    }
    return body;
  }

  Future<http.Response> _send(Future<http.Response> Function() run) async {
    try {
      return await run().timeout(const Duration(seconds: 30));
    } on TimeoutException {
      throw ApiException(
        'Server timed out. Try again or save offline.',
        code: 'network',
      );
    } on http.ClientException {
      throw ApiException(
        'No connection. Check Wi‑Fi / API_BASE, or save offline.',
        code: 'network',
      );
    } on ApiException {
      rethrow;
    } catch (_) {
      throw ApiException(
        'No connection. Check Wi‑Fi / API_BASE, or save offline.',
        code: 'network',
      );
    }
  }

  Future<Map<String, dynamic>> login(
    String username,
    String password, {
    bool forceLogin = false,
  }) async {
    final res = await _send(
      () => _client.post(
        _uri('/login.php'),
        headers: _headers,
        body: jsonEncode({
          'username': username,
          'password': password,
          'device_label': 'Flutter',
          if (forceLogin) 'force_login': true,
        }),
      ),
    );
    final body = _json(res);
    final token = body['token']?.toString() ?? body['data']?['token']?.toString();
    if (token != null && token.isNotEmpty) {
      _token = token;
    }
    return body;
  }

  Future<Map<String, dynamic>> me() async {
    final res = await _send(() => _client.get(_uri('/me.php'), headers: _headers));
    return _json(res);
  }

  Future<void> logout() async {
    try {
      await _send(
        () => _client.post(_uri('/logout.php'), headers: _headers, body: '{}'),
      );
    } catch (_) {
      // ignore offline logout
    } finally {
      _token = null;
    }
  }

  Future<Map<String, dynamic>> changePassword({
    required String currentPassword,
    required String newPassword,
    required String confirmPassword,
  }) async {
    final res = await _send(
      () => _client.post(
        _uri('/change-password.php'),
        headers: _headers,
        body: jsonEncode({
          'current_password': currentPassword,
          'new_password': newPassword,
          'confirm_password': confirmPassword,
        }),
      ),
    );
    return _json(res);
  }

  Future<List<dynamic>> barangays() async {
    final res = await _send(() => _client.get(_uri('/barangays.php'), headers: _headers));
    final body = _json(res);
    final data = body['barangays'] ?? body['data'] ?? [];
    return data is List ? data : [];
  }

  Future<List<dynamic>> puroks({required String barangayId}) async {
    final res = await _send(
      () => _client.get(
        _uri('/puroks.php', {'barangay_id': barangayId}),
        headers: _headers,
      ),
    );
    final body = _json(res);
    final data = body['puroks'] ?? body['data'] ?? [];
    return data is List ? data : [];
  }

  Future<Map<String, dynamic>> setContext({required String barangayId}) async {
    final res = await _send(
      () => _client.post(
        _uri('/context.php'),
        headers: _headers,
        body: jsonEncode({'barangay_id': barangayId}),
      ),
    );
    return _json(res);
  }

  Future<Map<String, dynamic>> dashboard() async {
    final res = await _send(() => _client.get(_uri('/dashboard.php'), headers: _headers));
    return _json(res);
  }

  Future<List<dynamic>> children({required String status}) async {
    final res = await _send(
      () => _client.get(
        _uri('/children.php', {'status': status}),
        headers: _headers,
      ),
    );
    final body = _json(res);
    final data = body['children'] ?? body['data'] ?? [];
    return data is List ? data : [];
  }

  Future<List<dynamic>> households() async {
    final res = await _send(() => _client.get(_uri('/households.php'), headers: _headers));
    final body = _json(res);
    final data = body['households'] ?? body['data'] ?? [];
    return data is List ? data : [];
  }

  Future<Map<String, dynamic>> household(String id, {bool full = true}) async {
    final res = await _send(
      () => _client.get(
        _uri('/households.php', {
          'id': id,
          if (full) 'full': '1',
        }),
        headers: _headers,
      ),
    );
    return _json(res);
  }

  Future<SurveyOptionsData> surveyOptions() async {
    final res = await _send(() => _client.get(_uri('/survey-options.php'), headers: _headers));
    final body = _json(res);
    return SurveyOptionsData.fromJson(body);
  }

  Future<Map<String, dynamic>> reports() async {
    final res = await _send(() => _client.get(_uri('/reports.php'), headers: _headers));
    return _json(res);
  }

  Future<Map<String, dynamic>> saveHouseholdSurvey(Map<String, dynamic> payload) async {
    final res = await _send(
      () => _client.post(
        _uri('/household-survey.php'),
        headers: _headers,
        body: jsonEncode(payload),
      ),
    );
    return _json(res);
  }
}

class SurveyOptionsData {
  SurveyOptionsData({
    required this.relationships,
    required this.genders,
    required this.toiletTypes,
    required this.toiletSanitary,
    required this.toiletUnsanitary,
    required this.garbageDisposal,
    required this.garbageUncollected,
    required this.waterSources,
    required this.houseOwnership,
    required this.dwellingTypes,
    required this.foodProductionActivities,
    required this.familyPlanningMethods,
    required this.complementaryMeals,
    required this.complementarySnacks,
    required this.childPhysicalActivity,
    required this.pregnantNutritionStatus,
  });

  final List<String> relationships;
  final List<String> genders;
  final List<String> toiletTypes;
  final List<String> toiletSanitary;
  final List<String> toiletUnsanitary;
  final List<String> garbageDisposal;
  final List<String> garbageUncollected;
  final List<String> waterSources;
  final List<String> houseOwnership;
  final List<String> dwellingTypes;
  final List<String> foodProductionActivities;
  final List<String> familyPlanningMethods;
  final List<String> complementaryMeals;
  final List<String> complementarySnacks;
  final List<String> childPhysicalActivity;
  final List<String> pregnantNutritionStatus;

  static List<String> _list(dynamic raw) {
    if (raw is! List) return [];
    final seen = <String>{};
    final out = <String>[];
    for (final e in raw) {
      final s = e.toString().trim();
      if (s.isEmpty || !seen.add(s)) continue;
      out.add(s);
    }
    return out;
  }

  factory SurveyOptionsData.fromJson(Map<String, dynamic> json) {
    return SurveyOptionsData(
      relationships: _list(json['relationships']),
      genders: _list(json['genders']),
      toiletTypes: _list(json['toilet_types']),
      toiletSanitary: _list(json['toilet_sanitary']),
      toiletUnsanitary: _list(json['toilet_unsanitary']),
      garbageDisposal: _list(json['garbage_disposal']),
      garbageUncollected: _list(json['garbage_uncollected']),
      waterSources: _list(json['water_sources']),
      houseOwnership: _list(json['house_ownership']),
      dwellingTypes: _list(json['dwelling_types']),
      foodProductionActivities: _list(json['food_production_activities']),
      familyPlanningMethods: _list(json['family_planning_methods']),
      complementaryMeals: _list(json['complementary_meals']),
      complementarySnacks: _list(json['complementary_snacks']),
      childPhysicalActivity: _list(json['child_physical_activity']),
      pregnantNutritionStatus: _list(json['pregnant_nutrition_status']),
    );
  }

  static SurveyOptionsData fallback() => SurveyOptionsData(
        relationships: const [
          'Spouse',
          'Common-law Husband',
          'Common-law Wife',
          'Son',
          'Daughter',
          'Father',
          'Mother',
          'Brother',
          'Sister',
          'Grandchild',
          'Grandparent',
          'Nephew',
          'Niece',
          'Other Relative',
        ],
        genders: const ['Male', 'Female'],
        toiletTypes: const [],
        toiletSanitary: const [],
        toiletUnsanitary: const [],
        garbageDisposal: const [],
        garbageUncollected: const [],
        waterSources: const [],
        houseOwnership: const [],
        dwellingTypes: const [],
        foodProductionActivities: const [],
        familyPlanningMethods: const [],
        complementaryMeals: const [],
        complementarySnacks: const [],
        childPhysicalActivity: const [],
        pregnantNutritionStatus: const [],
      );
}
