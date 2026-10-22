import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../api/nutrition_api.dart';
import '../offline/survey_queue.dart';
import '../session.dart';
import '../theme/app_theme.dart';

const _kOtherRelative = 'Other Relative';
const _kOthers = 'Others';

class _MemberDraft {
  _MemberDraft();

  String? memberId;
  final last = TextEditingController();
  final first = TextEditingController();
  final middle = TextEditingController();
  final weight = TextEditingController();
  final height = TextEditingController();
  final prevWeight = TextEditingController();
  final prevHeight = TextEditingController();
  final relationshipOther = TextEditingController();
  final pregnancyMonths = TextEditingController();
  final plannedOtherSpecify = TextEditingController();
  final lactatingOtherSpecify = TextEditingController();
  String gender = 'Male';
  String relationship = 'Son';
  String? pregnantNutritionStatus;
  DateTime? birth;
  DateTime? dateMeasured;
  DateTime? prevDateMeasured;
  bool isPregnant = false;
  bool isLactating = false;
  bool plannedExclusive = false;
  bool plannedMixed = false;
  bool plannedBottle = false;
  bool plannedOther = false;
  bool lactatingExclusive = false;
  bool lactatingMixed = false;
  bool lactatingBottle = false;
  bool lactatingOther = false;

  void dispose() {
    last.dispose();
    first.dispose();
    middle.dispose();
    weight.dispose();
    height.dispose();
    prevWeight.dispose();
    prevHeight.dispose();
    relationshipOther.dispose();
    pregnancyMonths.dispose();
    plannedOtherSpecify.dispose();
    lactatingOtherSpecify.dispose();
  }
}

/// Household survey encode / edit with web-parity sections.
class SurveyScreen extends StatefulWidget {
  const SurveyScreen({
    super.key,
    required this.session,
    this.surveyId,
    this.canEdit = true,
    this.onQueueChanged,
  });

  final Session session;
  final String? surveyId;
  final bool canEdit;
  final VoidCallback? onQueueChanged;

  @override
  State<SurveyScreen> createState() => _SurveyScreenState();
}

class _SurveyScreenState extends State<SurveyScreen> {
  final _formKey = GlobalKey<FormState>();
  final _queue = SurveyQueue();
  final _last = TextEditingController();
  final _first = TextEditingController();
  final _middle = TextEditingController();
  final _suffix = TextEditingController();
  final _occupation = TextEditingController();
  final _headPregnancyMonths = TextEditingController();
  final _headPlannedOtherSpecify = TextEditingController();
  final _headLactatingOtherSpecify = TextEditingController();
  final _houseOwnershipOther = TextEditingController();
  final _fpMethodsOther = TextEditingController();
  final _compMealsOther = TextEditingController();
  final _compSnacksOther = TextEditingController();
  final _activityOther = TextEditingController();
  final _remarks = TextEditingController();

  String? _editSurveyId;
  String? _purok;
  String _gender = 'Male';
  bool _is4ps = false;
  bool _isPwd = false;
  bool _isIp = false;
  bool _isSoloParent = false;
  bool _isNaMember = false;
  bool _headPregnant = false;
  bool _headLactating = false;
  String? _headPregnantNutritionStatus;
  bool _headPlannedExclusive = false;
  bool _headPlannedMixed = false;
  bool _headPlannedBottle = false;
  bool _headPlannedOther = false;
  bool _headLactatingExclusive = false;
  bool _headLactatingMixed = false;
  bool _headLactatingBottle = false;
  bool _headLactatingOther = false;
  bool _familyPlanning = false;
  bool _iodizedSalt = false;
  bool _sangkapPinoy = false;
  bool _carenderia = false;
  bool _sariSari = false;
  DateTime? _headBirth;
  DateTime _surveyDate = DateTime.now();
  bool _busy = false;
  bool _loadingEdit = false;
  List<String> _purokOptions = [];
  SurveyOptionsData _opts = SurveyOptionsData.fallback();

  String? _houseOwnership;
  String? _dwelling;
  String? _toilet;
  String? _water;
  String? _garbage;
  String? _garbageUncollected;
  String? _compMeals;
  String? _compSnacks;
  String? _childActivity;

  final Set<String> _foodProduction = {};
  final Set<String> _fpMethods = {};

  final List<_MemberDraft> _members = [_MemberDraft()];
  int _pendingCount = 0;

  bool get _isEdit => _editSurveyId != null && _editSurveyId!.isNotEmpty;

  @override
  void initState() {
    super.initState();
    _editSurveyId = widget.surveyId;
    _bootstrap();
  }

  Future<void> _bootstrap() async {
    await Future.wait([_loadPuroks(), _loadOptions(), _refreshQueueCount()]);
    if (_pendingCount > 0 && widget.surveyId == null) {
      try {
        await _queue.syncAll(widget.session.api);
        await _refreshQueueCount();
        widget.onQueueChanged?.call();
      } catch (_) {}
    }
    if (widget.surveyId != null && widget.surveyId!.isNotEmpty) {
      await _loadExistingSurvey(widget.surveyId!);
    }
  }

  Future<void> _loadExistingSurvey(String id) async {
    setState(() => _loadingEdit = true);
    try {
      final body = await widget.session.api.household(id);
      final raw = body['survey'];
      if (raw is Map<String, dynamic>) {
        _applySurvey(raw);
      } else if (raw is Map) {
        _applySurvey(Map<String, dynamic>.from(raw));
      }
    } on ApiException catch (e) {
      if (await widget.session.expireIfUnauthorized(e)) return;
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
      }
    } finally {
      if (mounted) setState(() => _loadingEdit = false);
    }
  }

  void _applySurvey(Map<String, dynamic> s) {
    _editSurveyId = s['existing_survey_id']?.toString() ?? s['survey_id']?.toString();
    _purok = s['purok_label']?.toString();
    _surveyDate = _parseDate(s['survey_date']?.toString()) ?? _surveyDate;
    _last.text = s['head_last_name']?.toString() ?? '';
    _first.text = s['head_first_name']?.toString() ?? '';
    _middle.text = s['head_middle_name']?.toString() ?? '';
    _suffix.text = s['head_suffix']?.toString() ?? '';
    _occupation.text = s['occupation']?.toString() ?? '';
    _headBirth = _parseDate(s['birth_date']?.toString());
    _gender = s['gender']?.toString() ?? 'Male';
    _is4ps = _apiBool(s['is_4ps']);
    _isPwd = _apiBool(s['is_pwd']);
    _isIp = _apiBool(s['is_ip']);
    _isSoloParent = _apiBool(s['is_solo_parent']);
    _isNaMember = _apiBool(s['is_na_member']);
    _headPregnant = _apiBool(s['head_is_pregnant']);
    _headLactating = _apiBool(s['head_is_lactating']);
    _headPregnancyMonths.text = s['head_pregnancy_months']?.toString() ?? '';
    _headPregnantNutritionStatus = _emptyToNull(s['head_pregnant_nutrition_status']?.toString());
    _headPlannedExclusive = _apiBool(s['head_planned_exclusive_breastfeeding']);
    _headPlannedMixed = _apiBool(s['head_planned_mixed_feeding']);
    _headPlannedBottle = _apiBool(s['head_planned_bottle_feeding']);
    _headPlannedOther = _apiBool(s['head_planned_other_feeding']);
    _headPlannedOtherSpecify.text = s['head_planned_other_specify']?.toString() ?? '';
    _headLactatingExclusive = _apiBool(s['head_lactating_exclusive_breastfeeding']);
    _headLactatingMixed = _apiBool(s['head_lactating_mixed_feeding']);
    _headLactatingBottle = _apiBool(s['head_lactating_bottle_feeding']);
    _headLactatingOther = _apiBool(s['head_lactating_other_feeding']);
    _headLactatingOtherSpecify.text = s['head_lactating_other_specify']?.toString() ?? '';
    _houseOwnership = _emptyToNull(s['house_ownership']?.toString());
    _houseOwnershipOther.text = s['house_ownership_other']?.toString() ?? '';
    _dwelling = _emptyToNull(s['dwelling_type']?.toString());
    _toilet = _emptyToNull(s['toilet_type']?.toString());
    _garbage = _emptyToNull(s['garbage_disposal']?.toString());
    _garbageUncollected = _emptyToNull(s['garbage_uncollected_type']?.toString());
    _water = _emptyToNull(s['water_source']?.toString());
    _foodProduction
      ..clear()
      ..addAll(_stringList(s['food_production_activities']));
    _iodizedSalt = _apiBool(s['uses_iodized_salt']);
    _sangkapPinoy = _apiBool(s['uses_sangkap_pinoy']);
    _carenderia = _apiBool(s['has_carenderia']);
    _sariSari = _apiBool(s['has_sari_sari_store']);
    _familyPlanning = _apiBool(s['practices_family_planning']);
    _fpMethods
      ..clear()
      ..addAll(_stringList(s['family_planning_methods_list']));
    _fpMethodsOther.text = s['family_planning_methods_other']?.toString() ?? '';
    _compMeals = _emptyToNull(s['complementary_meals']?.toString());
    _compMealsOther.text = s['complementary_meals_other']?.toString() ?? '';
    _compSnacks = _emptyToNull(s['complementary_snacks']?.toString());
    _compSnacksOther.text = s['complementary_snacks_other']?.toString() ?? '';
    _childActivity = _emptyToNull(s['child_physical_activity']?.toString());
    _activityOther.text = s['child_physical_activity_other']?.toString() ?? '';
    _remarks.text = s['remarks']?.toString() ?? '';

    for (final m in _members) {
      m.dispose();
    }
    _members.clear();
    final fam = s['family_members'];
    if (fam is List && fam.isNotEmpty) {
      for (final raw in fam) {
        if (raw is! Map) continue;
        final map = Map<String, dynamic>.from(raw);
        final d = _MemberDraft();
        d.memberId = map['member_id']?.toString();
        d.last.text = map['last_name']?.toString() ?? '';
        d.first.text = map['first_name']?.toString() ?? '';
        d.middle.text = map['middle_name']?.toString() ?? '';
        if (d.first.text.isEmpty && map['member_name'] != null) {
          final parts = map['member_name'].toString().split(',');
          if (parts.length >= 2) {
            d.last.text = parts[0].trim();
            d.first.text = parts.sublist(1).join(',').trim();
          }
        }
        d.relationship = _normalizeRelationship(
          map['relationship']?.toString() ?? 'Son',
          gender: map['gender']?.toString(),
        );
        d.relationshipOther.text = map['relationship_other']?.toString() ?? '';
        d.gender = map['gender']?.toString() ?? 'Male';
        d.birth = _parseDate(map['birth_date']?.toString());
        d.dateMeasured = _parseDate(map['date_measured']?.toString());
        d.prevDateMeasured = _parseDate(map['prev_date_measured']?.toString());
        d.weight.text = map['weight_kg']?.toString() ?? '';
        d.height.text = map['height_cm']?.toString() ?? '';
        d.prevWeight.text = map['prev_weight_kg']?.toString() ?? '';
        d.prevHeight.text = map['prev_height_cm']?.toString() ?? '';
        d.isPregnant = _apiBool(map['is_pregnant']);
        d.isLactating = _apiBool(map['is_lactating']);
        d.pregnancyMonths.text = map['pregnancy_months']?.toString() ?? '';
        d.pregnantNutritionStatus = _emptyToNull(map['pregnant_nutrition_status']?.toString());
        d.plannedExclusive = _apiBool(map['planned_exclusive_breastfeeding']);
        d.plannedMixed = _apiBool(map['planned_mixed_feeding']);
        d.plannedBottle = _apiBool(map['planned_bottle_feeding']);
        d.plannedOther = _apiBool(map['planned_other_feeding']);
        d.plannedOtherSpecify.text = map['planned_other_specify']?.toString() ?? '';
        d.lactatingExclusive = _apiBool(map['lactating_exclusive_breastfeeding']);
        d.lactatingMixed = _apiBool(map['lactating_mixed_feeding']);
        d.lactatingBottle = _apiBool(map['lactating_bottle_feeding']);
        d.lactatingOther = _apiBool(map['lactating_other_feeding']);
        d.lactatingOtherSpecify.text = map['lactating_other_specify']?.toString() ?? '';
        _members.add(d);
      }
    } else {
      _members.add(_MemberDraft());
    }
    if (_purok != null && _purok!.isNotEmpty && !_purokOptions.contains(_purok)) {
      _purokOptions = [..._purokOptions, _purok!];
    }
    setState(() {});
  }

  Future<void> _refreshQueueCount() async {
    final list = await _queue.list();
    if (mounted) setState(() => _pendingCount = list.length);
  }

  @override
  void dispose() {
    _last.dispose();
    _first.dispose();
    _middle.dispose();
    _suffix.dispose();
    _occupation.dispose();
    _headPregnancyMonths.dispose();
    _headPlannedOtherSpecify.dispose();
    _headLactatingOtherSpecify.dispose();
    _houseOwnershipOther.dispose();
    _fpMethodsOther.dispose();
    _compMealsOther.dispose();
    _compSnacksOther.dispose();
    _activityOther.dispose();
    _remarks.dispose();
    for (final m in _members) {
      m.dispose();
    }
    super.dispose();
  }

  bool _apiBool(dynamic v) {
    if (v is bool) return v;
    return v?.toString().toUpperCase() == 'YES';
  }

  String? _emptyToNull(String? s) {
    if (s == null || s.trim().isEmpty) return null;
    return s;
  }

  DateTime? _parseDate(String? s) {
    if (s == null || s.isEmpty) return null;
    return DateTime.tryParse(s) ?? DateFormat('yyyy-MM-dd').tryParse(s);
  }

  List<String> _stringList(dynamic raw) {
    if (raw is! List) return [];
    return raw.map((e) => e.toString()).where((e) => e.isNotEmpty).toList();
  }

  Future<void> _loadPuroks() async {
    final sessionPuroks = widget.session.puroks
        .map((e) => e.toString())
        .where((e) => e.isNotEmpty)
        .toList();
    if (sessionPuroks.isNotEmpty) {
      setState(() {
        _purokOptions = sessionPuroks;
        _purok ??= _purokOptions.first;
      });
      return;
    }
    final bid = widget.session.activeBarangayId;
    if (bid == null || bid.isEmpty) return;
    try {
      final list = await widget.session.api.puroks(barangayId: bid);
      setState(() {
        _purokOptions = list.map((e) => e.toString()).toList();
        if (_purokOptions.isNotEmpty) _purok ??= _purokOptions.first;
      });
    } catch (_) {}
  }

  /// Maps legacy "Son/Daughter" (and similar) onto current option labels.
  static String _normalizeRelationship(String raw, {String? gender}) {
    final r = raw.trim();
    if (r.isEmpty) return 'Son';
    if (r == 'Son/Daughter' || r == 'Son / Daughter') {
      return gender == 'Female' ? 'Daughter' : 'Son';
    }
    return r;
  }

  void _alignMemberRelationships() {
    if (_opts.relationships.isEmpty) return;
    for (final m in _members) {
      m.relationship = _normalizeRelationship(m.relationship, gender: m.gender);
      if (!_opts.relationships.contains(m.relationship)) {
        m.relationship = _opts.relationships.contains('Son')
            ? 'Son'
            : _opts.relationships.first;
      }
    }
  }

  Future<void> _loadOptions() async {
    try {
      final data = await widget.session.api.surveyOptions();
      setState(() {
        _opts = data;
        _alignMemberRelationships();
        _toilet ??= _opts.toiletTypes.isNotEmpty ? _opts.toiletTypes.first : null;
        _water ??= _opts.waterSources.isNotEmpty ? _opts.waterSources.first : null;
        _garbage ??= _opts.garbageDisposal.isNotEmpty ? _opts.garbageDisposal.first : null;
      });
    } catch (_) {
      setState(() {
        _opts = SurveyOptionsData.fallback();
        _alignMemberRelationships();
      });
    }
  }

  Future<void> _pickDate({
    required DateTime initial,
    required void Function(DateTime) onPick,
  }) async {
    final picked = await showDatePicker(
      context: context,
      initialDate: initial,
      firstDate: DateTime(1990),
      lastDate: DateTime.now(),
    );
    if (picked != null) onPick(picked);
  }

  Map<String, dynamic> _buildPayload() {
    final fmt = DateFormat('yyyy-MM-dd');
    final members = <Map<String, dynamic>>[];
    for (final m in _members) {
      final first = m.first.text.trim();
      if (first.isEmpty) continue;
      final last = m.last.text.trim().isEmpty ? _last.text.trim() : m.last.text.trim();
      final middle = m.middle.text.trim();
      final memberName = middle.isEmpty ? '$last, $first' : '$last, $first $middle';
      members.add({
        if (m.memberId != null && m.memberId!.isNotEmpty) 'member_id': m.memberId,
        'last_name': last,
        'first_name': first,
        'middle_name': middle,
        'member_name': memberName,
        'relationship': m.relationship,
        if (m.relationship == _kOtherRelative)
          'relationship_other': m.relationshipOther.text.trim(),
        'gender': m.gender,
        if (m.birth != null) 'birth_date': fmt.format(m.birth!),
        if (m.dateMeasured != null) 'date_measured': fmt.format(m.dateMeasured!),
        'weight_kg': double.tryParse(m.weight.text.trim().replaceAll(',', '.')),
        'height_cm': double.tryParse(m.height.text.trim().replaceAll(',', '.')),
        if (m.prevWeight.text.trim().isNotEmpty)
          'prev_weight_kg': double.tryParse(m.prevWeight.text.trim().replaceAll(',', '.')),
        if (m.prevHeight.text.trim().isNotEmpty)
          'prev_height_cm': double.tryParse(m.prevHeight.text.trim().replaceAll(',', '.')),
        if (m.prevDateMeasured != null) 'prev_date_measured': fmt.format(m.prevDateMeasured!),
        if (m.gender == 'Female') 'is_pregnant': m.isPregnant,
        if (m.gender == 'Female') 'is_lactating': m.isLactating,
        if (m.isPregnant) 'pregnancy_months': m.pregnancyMonths.text.trim(),
        if (m.isPregnant && m.pregnantNutritionStatus != null)
          'pregnant_nutrition_status': m.pregnantNutritionStatus,
        if (m.isPregnant) 'planned_exclusive_breastfeeding': m.plannedExclusive,
        if (m.isPregnant) 'planned_mixed_feeding': m.plannedMixed,
        if (m.isPregnant) 'planned_bottle_feeding': m.plannedBottle,
        if (m.isPregnant) 'planned_other_feeding': m.plannedOther,
        if (m.plannedOther) 'planned_other_specify': m.plannedOtherSpecify.text.trim(),
        if (m.isLactating) 'lactating_exclusive_breastfeeding': m.lactatingExclusive,
        if (m.isLactating) 'lactating_mixed_feeding': m.lactatingMixed,
        if (m.isLactating) 'lactating_bottle_feeding': m.lactatingBottle,
        if (m.isLactating) 'lactating_other_feeding': m.lactatingOther,
        if (m.lactatingOther) 'lactating_other_specify': m.lactatingOtherSpecify.text.trim(),
      });
    }

    return {
      if (_isEdit) 'existing_survey_id': _editSurveyId,
      'purok_label': _purok,
      'survey_date': fmt.format(_surveyDate),
      'head_last_name': _last.text.trim(),
      'head_first_name': _first.text.trim(),
      'head_middle_name': _middle.text.trim(),
      'head_suffix': _suffix.text.trim(),
      if (_headBirth != null) 'birth_date': fmt.format(_headBirth!),
      'gender': _gender,
      'occupation': _occupation.text.trim(),
      'is_4ps': _is4ps,
      'is_pwd': _isPwd,
      'is_ip': _isIp,
      'is_solo_parent': _isSoloParent,
      'is_na_member': _isNaMember,
      if (_gender == 'Female') 'head_is_pregnant': _headPregnant,
      if (_gender == 'Female') 'head_is_lactating': _headLactating,
      if (_headPregnant) 'head_pregnancy_months': _headPregnancyMonths.text.trim(),
      if (_headPregnant && _headPregnantNutritionStatus != null)
        'head_pregnant_nutrition_status': _headPregnantNutritionStatus,
      if (_headPregnant) 'head_planned_exclusive_breastfeeding': _headPlannedExclusive,
      if (_headPregnant) 'head_planned_mixed_feeding': _headPlannedMixed,
      if (_headPregnant) 'head_planned_bottle_feeding': _headPlannedBottle,
      if (_headPregnant) 'head_planned_other_feeding': _headPlannedOther,
      if (_headPlannedOther) 'head_planned_other_specify': _headPlannedOtherSpecify.text.trim(),
      if (_headLactating) 'head_lactating_exclusive_breastfeeding': _headLactatingExclusive,
      if (_headLactating) 'head_lactating_mixed_feeding': _headLactatingMixed,
      if (_headLactating) 'head_lactating_bottle_feeding': _headLactatingBottle,
      if (_headLactating) 'head_lactating_other_feeding': _headLactatingOther,
      if (_headLactatingOther) 'head_lactating_other_specify': _headLactatingOtherSpecify.text.trim(),
      if (_houseOwnership != null) 'house_ownership': _houseOwnership,
      if (_needsOther(_houseOwnership)) 'house_ownership_other': _houseOwnershipOther.text.trim(),
      if (_toilet != null) 'toilet_type': _toilet,
      if (_garbage != null) 'garbage_disposal': _garbage,
      if (_garbage == 'Uncollected' && _garbageUncollected != null)
        'garbage_uncollected_type': _garbageUncollected,
      if (_water != null) 'water_source': _water,
      if (_dwelling != null) 'dwelling_type': _dwelling,
      'food_production_activities': _foodProduction.toList(),
      'uses_iodized_salt': _iodizedSalt,
      'uses_sangkap_pinoy': _sangkapPinoy,
      'has_carenderia': _carenderia,
      'has_sari_sari_store': _sariSari,
      'practices_family_planning': _familyPlanning,
      if (_familyPlanning) 'family_planning_methods_list': _fpMethods.toList(),
      if (_familyPlanning && _fpMethods.contains(_kOthers))
        'family_planning_methods_other': _fpMethodsOther.text.trim(),
      if (_compMeals != null) 'complementary_meals': _compMeals,
      if (_needsOther(_compMeals)) 'complementary_meals_other': _compMealsOther.text.trim(),
      if (_compSnacks != null) 'complementary_snacks': _compSnacks,
      if (_needsOther(_compSnacks)) 'complementary_snacks_other': _compSnacksOther.text.trim(),
      if (_childActivity != null) 'child_physical_activity': _childActivity,
      if (_needsOther(_childActivity)) 'child_physical_activity_other': _activityOther.text.trim(),
      'remarks': _remarks.text.trim(),
      'family_members': members,
    };
  }

  bool _needsOther(String? value) =>
      value != null && (value == _kOthers || value.toLowerCase().contains('other'));

  void _resetForm() {
    _formKey.currentState?.reset();
    _last.clear();
    _first.clear();
    _middle.clear();
    _suffix.clear();
    _occupation.clear();
    _editSurveyId = null;
    for (final m in _members) {
      m.dispose();
    }
    _members
      ..clear()
      ..add(_MemberDraft());
    setState(() {
      _surveyDate = DateTime.now();
      _gender = 'Male';
      _headBirth = null;
      _headPregnant = false;
      _headLactating = false;
      _familyPlanning = false;
      _foodProduction.clear();
      _fpMethods.clear();
    });
  }

  Future<void> _save({required bool forceOffline}) async {
    if (!widget.canEdit) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('You do not have permission to save surveys.')),
      );
      return;
    }
    if (!_formKey.currentState!.validate()) return;
    if (_purok == null || _purok!.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Select a purok.')),
      );
      return;
    }
    final hasChild = _members.any((m) => m.first.text.trim().isNotEmpty);
    if (!hasChild) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Add at least one family member / child.')),
      );
      return;
    }
    for (final m in _members) {
      if (m.first.text.trim().isEmpty) continue;
      if (m.birth == null) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Birth date is required for each member.')),
        );
        return;
      }
      if (m.relationship == _kOtherRelative && m.relationshipOther.text.trim().isEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Specify relationship for Other Relative.')),
        );
        return;
      }
    }

    final payload = _buildPayload();
    setState(() => _busy = true);

    if (forceOffline) {
      await _queue.enqueue(payload);
      await _refreshQueueCount();
      widget.onQueueChanged?.call();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Saved offline. Sync when online.')),
      );
      if (!_isEdit) _resetForm();
      setState(() => _busy = false);
      return;
    }

    try {
      final res = await widget.session.api.saveHouseholdSurvey(payload);
      if (!mounted) return;
      final msg = res['message']?.toString() ?? 'Survey saved.';
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));
      if (_isEdit && widget.surveyId != null) {
        Navigator.of(context).maybePop();
      } else if (!_isEdit) {
        _resetForm();
      }
    } on ApiException catch (e) {
      if (await widget.session.expireIfUnauthorized(e)) return;
      if (!mounted) return;
      if (e.isNetwork) {
        final offline = await showDialog<bool>(
          context: context,
          builder: (ctx) => AlertDialog(
            title: const Text('No connection'),
            content: Text('${e.message}\n\nSave this survey offline and sync later?'),
            actions: [
              TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
              FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Save offline')),
            ],
          ),
        );
        if (offline == true) {
          await _queue.enqueue(payload);
          await _refreshQueueCount();
          widget.onQueueChanged?.call();
          if (!mounted) return;
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Saved offline. Sync when online.')),
          );
          if (!_isEdit) _resetForm();
        }
      } else {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Widget? _dropdown({
    required String label,
    required String? value,
    required List<String> options,
    required ValueChanged<String?> onChanged,
  }) {
    if (options.isEmpty) return null;
    final unique = <String>[...{...options}];
    final selected = (value != null && unique.contains(value)) ? value : null;
    return DropdownButtonFormField<String>(
      key: ValueKey('dd_$label/${selected ?? 'none'}/${unique.join('|')}'),
      initialValue: selected,
      isExpanded: true,
      decoration: InputDecoration(labelText: label),
      items: unique
          .map((p) => DropdownMenuItem(value: p, child: Text(p, overflow: TextOverflow.ellipsis)))
          .toList(),
      onChanged: _busy ? null : onChanged,
    );
  }

  Widget _chipMulti(String title, List<String> options, Set<String> selected) {
    if (options.isEmpty) return const SizedBox.shrink();
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Text(title, style: Theme.of(context).textTheme.titleSmall),
        const SizedBox(height: 8),
        Wrap(
          spacing: 8,
          runSpacing: 4,
          children: options.map((opt) {
            final on = selected.contains(opt);
            return FilterChip(
              label: Text(opt),
              selected: on,
              onSelected: _busy
                  ? null
                  : (v) => setState(() {
                        if (v) {
                          selected.add(opt);
                        } else {
                          selected.remove(opt);
                        }
                      }),
            );
          }).toList(),
        ),
        const SizedBox(height: 8),
      ],
    );
  }

  Widget _feedingPlan({
    required String prefix,
    required bool planned,
    required bool exclusive,
    required bool mixed,
    required bool bottle,
    required bool other,
    required TextEditingController otherSpecify,
    required void Function(bool exclusive, bool mixed, bool bottle, bool other) onChanged,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Text(prefix, style: Theme.of(context).textTheme.labelLarge),
        SwitchListTile(
          contentPadding: EdgeInsets.zero,
          title: const Text('Exclusive breastfeeding'),
          value: exclusive,
          onChanged: _busy ? null : (v) => setState(() => onChanged(v, mixed, bottle, other)),
        ),
        SwitchListTile(
          contentPadding: EdgeInsets.zero,
          title: const Text('Mixed feeding'),
          value: mixed,
          onChanged: _busy ? null : (v) => setState(() => onChanged(exclusive, v, bottle, other)),
        ),
        SwitchListTile(
          contentPadding: EdgeInsets.zero,
          title: const Text('Bottle feeding'),
          value: bottle,
          onChanged: _busy ? null : (v) => setState(() => onChanged(exclusive, mixed, v, other)),
        ),
        SwitchListTile(
          contentPadding: EdgeInsets.zero,
          title: const Text('Other feeding'),
          value: other,
          onChanged: _busy ? null : (v) => setState(() => onChanged(exclusive, mixed, bottle, v)),
        ),
        if (other)
          TextFormField(
            controller: otherSpecify,
            decoration: const InputDecoration(labelText: 'Specify other feeding'),
          ),
      ],
    );
  }

  Widget _memberCard(int i, _MemberDraft m) {
    final dateFmt = DateFormat.yMMMd();
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Row(
              children: [
                Text('Member ${i + 1}', style: Theme.of(context).textTheme.titleSmall),
                const Spacer(),
                if (_members.length > 1)
                  IconButton(
                    tooltip: 'Remove',
                    onPressed: _busy
                        ? null
                        : () => setState(() {
                              m.dispose();
                              _members.removeAt(i);
                            }),
                    icon: const Icon(Icons.delete_outline),
                  ),
              ],
            ),
            TextFormField(
              controller: m.first,
              decoration: const InputDecoration(labelText: 'First name'),
              validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null,
            ),
            const SizedBox(height: 8),
            TextFormField(
              controller: m.last,
              decoration: const InputDecoration(
                labelText: 'Last name (optional — uses head last name)',
              ),
            ),
            const SizedBox(height: 8),
            TextFormField(controller: m.middle, decoration: const InputDecoration(labelText: 'Middle name')),
            const SizedBox(height: 8),
            Builder(
              builder: (context) {
                final relOpts = _opts.relationships.isNotEmpty
                    ? _opts.relationships
                    : SurveyOptionsData.fallback().relationships;
                final rel = _dropdown(
                  label: 'Relationship',
                  value: _normalizeRelationship(m.relationship, gender: m.gender),
                  options: relOpts,
                  onChanged: (v) => setState(() => m.relationship = v ?? m.relationship),
                );
                return rel ?? const SizedBox.shrink();
              },
            ),
            if (m.relationship == _kOtherRelative) ...[
              const SizedBox(height: 8),
              TextFormField(
                controller: m.relationshipOther,
                decoration: const InputDecoration(labelText: 'Specify other relative'),
              ),
            ],
            const SizedBox(height: 8),
            DropdownButtonFormField<String>(
              key: ValueKey('member-gender-${m.gender}-$i'),
              initialValue: m.gender,
              decoration: const InputDecoration(labelText: 'Sex'),
              items: const [
                DropdownMenuItem(value: 'Male', child: Text('Male')),
                DropdownMenuItem(value: 'Female', child: Text('Female')),
              ],
              onChanged: _busy
                  ? null
                  : (v) => setState(() {
                        m.gender = v ?? 'Male';
                        if (m.gender != 'Female') {
                          m.isPregnant = false;
                          m.isLactating = false;
                        }
                      }),
            ),
            ListTile(
              contentPadding: EdgeInsets.zero,
              title: const Text('Birth date'),
              subtitle: Text(m.birth == null ? 'Tap to select' : dateFmt.format(m.birth!)),
              trailing: const Icon(Icons.calendar_today),
              onTap: _busy
                  ? null
                  : () => _pickDate(
                        initial: m.birth ?? DateTime.now().subtract(const Duration(days: 365)),
                        onPick: (d) => setState(() => m.birth = d),
                      ),
            ),
            if (m.gender == 'Female') ...[
              SwitchListTile(
                contentPadding: EdgeInsets.zero,
                title: const Text('Pregnant'),
                value: m.isPregnant,
                onChanged: _busy ? null : (v) => setState(() => m.isPregnant = v),
              ),
              SwitchListTile(
                contentPadding: EdgeInsets.zero,
                title: const Text('Lactating'),
                value: m.isLactating,
                onChanged: _busy ? null : (v) => setState(() => m.isLactating = v),
              ),
              if (m.isPregnant) ...[
                TextFormField(
                  controller: m.pregnancyMonths,
                  decoration: const InputDecoration(labelText: 'Pregnancy months'),
                  keyboardType: TextInputType.number,
                ),
                const SizedBox(height: 8),
                if (_dropdown(
                      label: 'Pregnant nutrition status',
                      value: m.pregnantNutritionStatus,
                      options: _opts.pregnantNutritionStatus,
                      onChanged: (v) => setState(() => m.pregnantNutritionStatus = v),
                    ) !=
                    null)
                  _dropdown(
                    label: 'Pregnant nutrition status',
                    value: m.pregnantNutritionStatus,
                    options: _opts.pregnantNutritionStatus,
                    onChanged: (v) => setState(() => m.pregnantNutritionStatus = v),
                  )!,
                _feedingPlan(
                  prefix: 'Planned feeding',
                  planned: true,
                  exclusive: m.plannedExclusive,
                  mixed: m.plannedMixed,
                  bottle: m.plannedBottle,
                  other: m.plannedOther,
                  otherSpecify: m.plannedOtherSpecify,
                  onChanged: (a, b, c, d) {
                    m.plannedExclusive = a;
                    m.plannedMixed = b;
                    m.plannedBottle = c;
                    m.plannedOther = d;
                  },
                ),
              ],
              if (m.isLactating)
                _feedingPlan(
                  prefix: 'Lactating feeding',
                  planned: false,
                  exclusive: m.lactatingExclusive,
                  mixed: m.lactatingMixed,
                  bottle: m.lactatingBottle,
                  other: m.lactatingOther,
                  otherSpecify: m.lactatingOtherSpecify,
                  onChanged: (a, b, c, d) {
                    m.lactatingExclusive = a;
                    m.lactatingMixed = b;
                    m.lactatingBottle = c;
                    m.lactatingOther = d;
                  },
                ),
            ],
            ListTile(
              contentPadding: EdgeInsets.zero,
              title: const Text('Date measured'),
              subtitle: Text(m.dateMeasured == null ? 'Optional' : dateFmt.format(m.dateMeasured!)),
              trailing: const Icon(Icons.calendar_today),
              onTap: _busy
                  ? null
                  : () => _pickDate(
                        initial: m.dateMeasured ?? DateTime.now(),
                        onPick: (d) => setState(() => m.dateMeasured = d),
                      ),
            ),
            Row(
              children: [
                Expanded(
                  child: TextFormField(
                    controller: m.weight,
                    decoration: const InputDecoration(labelText: 'Weight (kg)'),
                    keyboardType: const TextInputType.numberWithOptions(decimal: true),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: TextFormField(
                    controller: m.height,
                    decoration: const InputDecoration(labelText: 'Height (cm)'),
                    keyboardType: const TextInputType.numberWithOptions(decimal: true),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Text('Previous measurement', style: Theme.of(context).textTheme.labelLarge),
            ListTile(
              contentPadding: EdgeInsets.zero,
              title: const Text('Prev. date measured'),
              subtitle: Text(
                m.prevDateMeasured == null ? 'Optional' : dateFmt.format(m.prevDateMeasured!),
              ),
              trailing: const Icon(Icons.calendar_today),
              onTap: _busy
                  ? null
                  : () => _pickDate(
                        initial: m.prevDateMeasured ?? DateTime.now(),
                        onPick: (d) => setState(() => m.prevDateMeasured = d),
                      ),
            ),
            Row(
              children: [
                Expanded(
                  child: TextFormField(
                    controller: m.prevWeight,
                    decoration: const InputDecoration(labelText: 'Prev. weight (kg)'),
                    keyboardType: const TextInputType.numberWithOptions(decimal: true),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: TextFormField(
                    controller: m.prevHeight,
                    decoration: const InputDecoration(labelText: 'Prev. height (cm)'),
                    keyboardType: const TextInputType.numberWithOptions(decimal: true),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _section({
    required String title,
    required List<Widget> children,
    bool initiallyExpanded = false,
    IconData? icon,
  }) {
    final border = BorderSide(color: AppColors.forest.withValues(alpha: 0.1));
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Material(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        child: Theme(
          data: Theme.of(context).copyWith(dividerColor: Colors.transparent),
          child: ExpansionTile(
            initiallyExpanded: initiallyExpanded,
            tilePadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 2),
            childrenPadding: const EdgeInsets.only(bottom: 10),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(16),
              side: border,
            ),
            collapsedShape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(16),
              side: border,
            ),
            leading: icon == null
                ? null
                : Icon(icon, color: AppColors.forest, size: 22),
            title: Text(
              title,
              style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15),
            ),
            children: children,
          ),
        ),
      ),
    );
  }

  Widget _buildFormBody() {
    final dateFmt = DateFormat.yMMMd();
    if (_loadingEdit) {
      return const Center(child: CircularProgressIndicator());
    }
    return Form(
      key: _formKey,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          if (_pendingCount > 0 && widget.surveyId == null) ...[
            Container(
              margin: const EdgeInsets.only(bottom: 10),
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: AppColors.mist,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: AppColors.forest.withValues(alpha: 0.12)),
              ),
              child: Row(
                children: [
                  const Icon(Icons.cloud_upload_outlined, color: AppColors.forest),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          '$_pendingCount survey(s) waiting to sync',
                          style: const TextStyle(fontWeight: FontWeight.w700),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          'Opening Encode tries a background sync.',
                          style: Theme.of(context).textTheme.bodySmall?.copyWith(
                                color: AppColors.muted,
                              ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ],
          _section(
            initiallyExpanded: true,
            icon: Icons.assignment_outlined,
            title: 'Survey information',
            children: [
              if (_purokOptions.isEmpty)
                const Padding(
                  padding: EdgeInsets.all(12),
                  child: Text('No puroks loaded. Set barangay context first.'),
                )
              else
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 12),
                  child: DropdownButtonFormField<String>(
                    key: ValueKey('purok-$_purok'),
                    initialValue: _purok,
                    decoration: const InputDecoration(labelText: 'Purok'),
                    items: _purokOptions
                        .map((p) => DropdownMenuItem(value: p, child: Text(p)))
                        .toList(),
                    onChanged: _busy ? null : (v) => setState(() => _purok = v),
                  ),
                ),
              ListTile(
                title: const Text('Survey date'),
                subtitle: Text(dateFmt.format(_surveyDate)),
                trailing: const Icon(Icons.calendar_today),
                onTap: _busy
                    ? null
                    : () => _pickDate(
                          initial: _surveyDate,
                          onPick: (d) => setState(() => _surveyDate = d),
                        ),
              ),
            ],
          ),
          _section(
            icon: Icons.person_outline_rounded,
            title: 'Household head',
            children: [
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 12),
                child: Column(
                  children: [
                    TextFormField(
                      controller: _last,
                      decoration: const InputDecoration(labelText: 'Last name'),
                      validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null,
                    ),
                    const SizedBox(height: 8),
                    TextFormField(
                      controller: _first,
                      decoration: const InputDecoration(labelText: 'First name'),
                      validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null,
                    ),
                    const SizedBox(height: 8),
                    TextFormField(controller: _middle, decoration: const InputDecoration(labelText: 'Middle name')),
                    const SizedBox(height: 8),
                    TextFormField(controller: _suffix, decoration: const InputDecoration(labelText: 'Suffix')),
                    ListTile(
                      contentPadding: EdgeInsets.zero,
                      title: const Text('Birth date'),
                      subtitle: Text(_headBirth == null ? 'Optional' : dateFmt.format(_headBirth!)),
                      trailing: const Icon(Icons.calendar_today),
                      onTap: _busy
                          ? null
                          : () => _pickDate(
                                initial: _headBirth ?? DateTime(1990),
                                onPick: (d) => setState(() => _headBirth = d),
                              ),
                    ),
                    TextFormField(
                      controller: _occupation,
                      decoration: const InputDecoration(labelText: 'Occupation'),
                    ),
                    const SizedBox(height: 8),
                    DropdownButtonFormField<String>(
                      key: ValueKey('head-gender-$_gender'),
                      initialValue: _gender,
                      decoration: const InputDecoration(labelText: 'Sex'),
                      items: const [
                        DropdownMenuItem(value: 'Male', child: Text('Male')),
                        DropdownMenuItem(value: 'Female', child: Text('Female')),
                      ],
                      onChanged: _busy
                          ? null
                          : (v) => setState(() {
                                _gender = v ?? 'Male';
                                if (_gender != 'Female') {
                                  _headPregnant = false;
                                  _headLactating = false;
                                }
                              }),
                    ),
                    SwitchListTile(
                      contentPadding: EdgeInsets.zero,
                      title: const Text('4Ps beneficiary'),
                      value: _is4ps,
                      onChanged: _busy ? null : (v) => setState(() => _is4ps = v),
                    ),
                    SwitchListTile(
                      contentPadding: EdgeInsets.zero,
                      title: const Text('PWD'),
                      value: _isPwd,
                      onChanged: _busy ? null : (v) => setState(() => _isPwd = v),
                    ),
                    SwitchListTile(
                      contentPadding: EdgeInsets.zero,
                      title: const Text('Indigenous people (IP)'),
                      value: _isIp,
                      onChanged: _busy ? null : (v) => setState(() => _isIp = v),
                    ),
                    SwitchListTile(
                      contentPadding: EdgeInsets.zero,
                      title: const Text('Solo parent'),
                      value: _isSoloParent,
                      onChanged: _busy ? null : (v) => setState(() => _isSoloParent = v),
                    ),
                    SwitchListTile(
                      contentPadding: EdgeInsets.zero,
                      title: const Text('NA member'),
                      value: _isNaMember,
                      onChanged: _busy ? null : (v) => setState(() => _isNaMember = v),
                    ),
                    if (_gender == 'Female') ...[
                      SwitchListTile(
                        contentPadding: EdgeInsets.zero,
                        title: const Text('Head is pregnant'),
                        value: _headPregnant,
                        onChanged: _busy ? null : (v) => setState(() => _headPregnant = v),
                      ),
                      SwitchListTile(
                        contentPadding: EdgeInsets.zero,
                        title: const Text('Head is lactating'),
                        value: _headLactating,
                        onChanged: _busy ? null : (v) => setState(() => _headLactating = v),
                      ),
                      if (_headPregnant) ...[
                        TextFormField(
                          controller: _headPregnancyMonths,
                          decoration: const InputDecoration(labelText: 'Pregnancy months'),
                          keyboardType: TextInputType.number,
                        ),
                        if (_dropdown(
                              label: 'Pregnant nutrition status',
                              value: _headPregnantNutritionStatus,
                              options: _opts.pregnantNutritionStatus,
                              onChanged: (v) => setState(() => _headPregnantNutritionStatus = v),
                            ) !=
                            null)
                          _dropdown(
                            label: 'Pregnant nutrition status',
                            value: _headPregnantNutritionStatus,
                            options: _opts.pregnantNutritionStatus,
                            onChanged: (v) => setState(() => _headPregnantNutritionStatus = v),
                          )!,
                        _feedingPlan(
                          prefix: 'Head planned feeding',
                          planned: true,
                          exclusive: _headPlannedExclusive,
                          mixed: _headPlannedMixed,
                          bottle: _headPlannedBottle,
                          other: _headPlannedOther,
                          otherSpecify: _headPlannedOtherSpecify,
                          onChanged: (a, b, c, d) => setState(() {
                            _headPlannedExclusive = a;
                            _headPlannedMixed = b;
                            _headPlannedBottle = c;
                            _headPlannedOther = d;
                          }),
                        ),
                      ],
                      if (_headLactating)
                        _feedingPlan(
                          prefix: 'Head lactating feeding',
                          planned: false,
                          exclusive: _headLactatingExclusive,
                          mixed: _headLactatingMixed,
                          bottle: _headLactatingBottle,
                          other: _headLactatingOther,
                          otherSpecify: _headLactatingOtherSpecify,
                          onChanged: (a, b, c, d) => setState(() {
                            _headLactatingExclusive = a;
                            _headLactatingMixed = b;
                            _headLactatingBottle = c;
                            _headLactatingOther = d;
                          }),
                        ),
                    ],
                  ],
                ),
              ),
            ],
          ),
          _section(
            icon: Icons.home_outlined,
            title: 'BSI living conditions',
            children: [
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 12),
                child: Column(
                  children: [
                    if (_dropdown(
                          label: 'House ownership',
                          value: _houseOwnership,
                          options: _opts.houseOwnership,
                          onChanged: (v) => setState(() => _houseOwnership = v),
                        ) !=
                        null)
                      _dropdown(
                        label: 'House ownership',
                        value: _houseOwnership,
                        options: _opts.houseOwnership,
                        onChanged: (v) => setState(() => _houseOwnership = v),
                      )!,
                    if (_needsOther(_houseOwnership)) ...[
                      const SizedBox(height: 8),
                      TextFormField(
                        controller: _houseOwnershipOther,
                        decoration: const InputDecoration(labelText: 'Specify ownership'),
                      ),
                    ],
                    const SizedBox(height: 8),
                    if (_dropdown(
                          label: 'Dwelling type',
                          value: _dwelling,
                          options: _opts.dwellingTypes,
                          onChanged: (v) => setState(() => _dwelling = v),
                        ) !=
                        null)
                      _dropdown(
                        label: 'Dwelling type',
                        value: _dwelling,
                        options: _opts.dwellingTypes,
                        onChanged: (v) => setState(() => _dwelling = v),
                      )!,
                    const SizedBox(height: 8),
                    if (_dropdown(
                          label: 'Toilet type',
                          value: _toilet,
                          options: _opts.toiletTypes,
                          onChanged: (v) => setState(() => _toilet = v),
                        ) !=
                        null)
                      _dropdown(
                        label: 'Toilet type',
                        value: _toilet,
                        options: _opts.toiletTypes,
                        onChanged: (v) => setState(() => _toilet = v),
                      )!,
                    const SizedBox(height: 8),
                    if (_dropdown(
                          label: 'Garbage disposal',
                          value: _garbage,
                          options: _opts.garbageDisposal,
                          onChanged: (v) => setState(() {
                            _garbage = v;
                            if (v != 'Uncollected') _garbageUncollected = null;
                          }),
                        ) !=
                        null)
                      _dropdown(
                        label: 'Garbage disposal',
                        value: _garbage,
                        options: _opts.garbageDisposal,
                        onChanged: (v) => setState(() {
                          _garbage = v;
                          if (v != 'Uncollected') _garbageUncollected = null;
                        }),
                      )!,
                    if (_garbage == 'Uncollected' && _opts.garbageUncollected.isNotEmpty) ...[
                      const SizedBox(height: 8),
                      if (_dropdown(
                            label: 'If uncollected',
                            value: _garbageUncollected ?? _opts.garbageUncollected.first,
                            options: _opts.garbageUncollected,
                            onChanged: (v) => setState(() => _garbageUncollected = v),
                          ) !=
                          null)
                        _dropdown(
                          label: 'If uncollected',
                          value: _garbageUncollected ?? _opts.garbageUncollected.first,
                          options: _opts.garbageUncollected,
                          onChanged: (v) => setState(() => _garbageUncollected = v),
                        )!,
                    ],
                    const SizedBox(height: 8),
                    if (_dropdown(
                          label: 'Drinking water source',
                          value: _water,
                          options: _opts.waterSources,
                          onChanged: (v) => setState(() => _water = v),
                        ) !=
                        null)
                      _dropdown(
                        label: 'Drinking water source',
                        value: _water,
                        options: _opts.waterSources,
                        onChanged: (v) => setState(() => _water = v),
                      )!,
                    const SizedBox(height: 8),
                    _chipMulti('Food production', _opts.foodProductionActivities, _foodProduction),
                    SwitchListTile(
                      contentPadding: EdgeInsets.zero,
                      title: const Text('Uses iodized salt'),
                      value: _iodizedSalt,
                      onChanged: _busy ? null : (v) => setState(() => _iodizedSalt = v),
                    ),
                    SwitchListTile(
                      contentPadding: EdgeInsets.zero,
                      title: const Text('Uses Sangkap Pinoy'),
                      value: _sangkapPinoy,
                      onChanged: _busy ? null : (v) => setState(() => _sangkapPinoy = v),
                    ),
                    SwitchListTile(
                      contentPadding: EdgeInsets.zero,
                      title: const Text('Has carenderia'),
                      value: _carenderia,
                      onChanged: _busy ? null : (v) => setState(() => _carenderia = v),
                    ),
                    SwitchListTile(
                      contentPadding: EdgeInsets.zero,
                      title: const Text('Has sari-sari store'),
                      value: _sariSari,
                      onChanged: _busy ? null : (v) => setState(() => _sariSari = v),
                    ),
                  ],
                ),
              ),
            ],
          ),
          _section(
            icon: Icons.favorite_border_rounded,
            title: 'Family planning',
            children: [
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 12),
                child: Column(
                  children: [
                    SwitchListTile(
                      contentPadding: EdgeInsets.zero,
                      title: const Text('Practices family planning'),
                      value: _familyPlanning,
                      onChanged: _busy ? null : (v) => setState(() => _familyPlanning = v),
                    ),
                    if (_familyPlanning) ...[
                      _chipMulti('Methods', _opts.familyPlanningMethods, _fpMethods),
                      if (_fpMethods.contains(_kOthers))
                        TextFormField(
                          controller: _fpMethodsOther,
                          decoration: const InputDecoration(labelText: 'Specify other FP method'),
                        ),
                    ],
                  ],
                ),
              ),
            ],
          ),
          _section(
            icon: Icons.restaurant_outlined,
            title: 'Complementary feeding & activity',
            children: [
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 12),
                child: Column(
                  children: [
                    if (_dropdown(
                          label: 'Complementary meals',
                          value: _compMeals,
                          options: _opts.complementaryMeals,
                          onChanged: (v) => setState(() => _compMeals = v),
                        ) !=
                        null)
                      _dropdown(
                        label: 'Complementary meals',
                        value: _compMeals,
                        options: _opts.complementaryMeals,
                        onChanged: (v) => setState(() => _compMeals = v),
                      )!,
                    if (_needsOther(_compMeals)) ...[
                      const SizedBox(height: 8),
                      TextFormField(
                        controller: _compMealsOther,
                        decoration: const InputDecoration(labelText: 'Specify meals'),
                      ),
                    ],
                    const SizedBox(height: 8),
                    if (_dropdown(
                          label: 'Complementary snacks',
                          value: _compSnacks,
                          options: _opts.complementarySnacks,
                          onChanged: (v) => setState(() => _compSnacks = v),
                        ) !=
                        null)
                      _dropdown(
                        label: 'Complementary snacks',
                        value: _compSnacks,
                        options: _opts.complementarySnacks,
                        onChanged: (v) => setState(() => _compSnacks = v),
                      )!,
                    if (_needsOther(_compSnacks)) ...[
                      const SizedBox(height: 8),
                      TextFormField(
                        controller: _compSnacksOther,
                        decoration: const InputDecoration(labelText: 'Specify snacks'),
                      ),
                    ],
                    const SizedBox(height: 8),
                    if (_dropdown(
                          label: 'Child physical activity',
                          value: _childActivity,
                          options: _opts.childPhysicalActivity,
                          onChanged: (v) => setState(() => _childActivity = v),
                        ) !=
                        null)
                      _dropdown(
                        label: 'Child physical activity',
                        value: _childActivity,
                        options: _opts.childPhysicalActivity,
                        onChanged: (v) => setState(() => _childActivity = v),
                      )!,
                    if (_needsOther(_childActivity)) ...[
                      const SizedBox(height: 8),
                      TextFormField(
                        controller: _activityOther,
                        decoration: const InputDecoration(labelText: 'Specify activity'),
                      ),
                    ],
                  ],
                ),
              ),
            ],
          ),
          _section(
            icon: Icons.notes_rounded,
            title: 'Remarks',
            children: [
              Padding(
                padding: const EdgeInsets.all(12),
                child: TextFormField(
                  controller: _remarks,
                  decoration: const InputDecoration(
                    labelText: 'Remarks',
                    alignLabelWithHint: true,
                  ),
                  maxLines: 4,
                ),
              ),
            ],
          ),
          _section(
            initiallyExpanded: true,
            icon: Icons.groups_outlined,
            title: 'Family members',
            children: [
              Align(
                alignment: Alignment.centerRight,
                child: TextButton.icon(
                  onPressed: _busy ? null : () => setState(() => _members.add(_MemberDraft())),
                  icon: const Icon(Icons.person_add_alt_1),
                  label: const Text('Add member'),
                ),
              ),
              ...List.generate(_members.length, (i) => _memberCard(i, _members[i])),
            ],
          ),
          const SizedBox(height: 8),
          if (widget.canEdit) ...[
            SizedBox(
              width: double.infinity,
              child: FilledButton(
                onPressed: _busy ? null : () => _save(forceOffline: false),
                child: _busy
                    ? const SizedBox(
                        height: 20,
                        width: 20,
                        child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                      )
                    : Text(_isEdit ? 'Update household survey' : 'Save household survey'),
              ),
            ),
            if (!_isEdit) ...[
              const SizedBox(height: 8),
              SizedBox(
                width: double.infinity,
                child: OutlinedButton.icon(
                  onPressed: _busy ? null : () => _save(forceOffline: true),
                  icon: const Icon(Icons.cloud_off_outlined),
                  label: const Text('Save offline'),
                ),
              ),
            ],
          ] else
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: AppColors.mist,
                borderRadius: BorderRadius.circular(14),
              ),
              child: Text(
                'View only — you cannot edit surveys with this account.',
                style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: AppColors.muted),
              ),
            ),
          const SizedBox(height: 32),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final body = _buildFormBody();
    if (widget.surveyId != null) {
      return Scaffold(
        appBar: AppBar(title: Text(widget.canEdit ? 'Edit survey' : 'Survey detail')),
        body: body,
      );
    }
    return body;
  }
}
