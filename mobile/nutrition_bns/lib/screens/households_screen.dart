import 'package:flutter/material.dart';

import '../api/nutrition_api.dart';
import '../session.dart';
import '../theme/app_theme.dart';
import '../widgets/app_ui.dart';
import 'survey_screen.dart';

class HouseholdsScreen extends StatefulWidget {
  const HouseholdsScreen({super.key, required this.session});

  final Session session;

  @override
  State<HouseholdsScreen> createState() => _HouseholdsScreenState();
}

bool householdFlagYes(Object? value) => value?.toString().toUpperCase() == 'YES';

Future<void> openHouseholdRecord(
  BuildContext context,
  Session session,
  String surveyId,
) async {
  try {
    final body = await session.api.household(surveyId);
    if (!context.mounted) return;
    final canEdit = body['can_edit'] == true;
    if (canEdit) {
      await Navigator.of(context).push<void>(
        MaterialPageRoute(
          builder: (_) => SurveyScreen(
            session: session,
            surveyId: surveyId,
            canEdit: true,
          ),
        ),
      );
      return;
    }
    await showHouseholdDetailSheet(context, body);
  } on ApiException catch (e) {
    if (await session.expireIfUnauthorized(e)) return;
    if (!context.mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
  }
}

Future<void> showHouseholdDetailSheet(BuildContext context, Map<String, dynamic> body) async {
  final hh = body['household'] is Map ? body['household'] as Map : {};
  final members = body['members'] is List ? body['members'] as List : [];
  await showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    showDragHandle: true,
    backgroundColor: Colors.white,
    shape: const RoundedRectangleBorder(
      borderRadius: BorderRadius.vertical(top: Radius.circular(22)),
    ),
    builder: (ctx) {
      return DraggableScrollableSheet(
        expand: false,
        initialChildSize: 0.72,
        maxChildSize: 0.95,
        builder: (_, controller) {
          return ListView(
            controller: controller,
            padding: const EdgeInsets.fromLTRB(20, 4, 20, 28),
            children: [
              Text(
                (hh['household_head'] ?? 'Household').toString(),
                style: Theme.of(ctx).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w800),
              ),
              const SizedBox(height: 6),
              Text(
                'Purok ${hh['purok_label'] ?? '—'} · Survey ${hh['survey_date'] ?? '—'}',
                style: Theme.of(ctx).textTheme.bodyMedium?.copyWith(color: AppColors.muted),
              ),
              const SizedBox(height: 8),
              Wrap(
                spacing: 6,
                runSpacing: 6,
                children: [
                  if (householdFlagYes(hh['has_pregnant']))
                    const _FlagChip(label: 'Pregnant', color: Color(0xFFAD1457)),
                  if (householdFlagYes(hh['has_lactating']))
                    const _FlagChip(label: 'Lactating', color: Color(0xFF1565C0)),
                  if (householdFlagYes(hh['moved_out']))
                    const _FlagChip(label: 'Moved out', color: Color(0xFF78909C)),
                ],
              ),
              const SizedBox(height: 12),
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: AppColors.mist,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Text(
                  'View only — your role cannot edit surveys.',
                  style: TextStyle(color: AppColors.muted, fontWeight: FontWeight.w500),
                ),
              ),
              const SizedBox(height: 18),
              AppSectionLabel('Members (${members.length})'),
              ...members.map((m) {
                if (m is! Map) return Text('$m');
                final flags = [
                  if (householdFlagYes(m['is_pregnant'])) 'Pregnant',
                  if (householdFlagYes(m['is_lactating'])) 'Lactating',
                  m['weight_for_age'],
                  m['height_for_age'],
                ].where((e) => e != null && '$e'.isNotEmpty && '$e' != '—').join(' · ');
                return Padding(
                  padding: const EdgeInsets.only(bottom: 8),
                  child: AppListCard(
                    title: (m['member_name'] ?? '').toString(),
                    subtitle: [
                      m['relationship'],
                      m['gender'],
                      if (m['age_months'] != null) '${m['age_months']} mo',
                      if (flags.isNotEmpty) flags,
                    ].where((e) => e != null && '$e'.isNotEmpty).join(' · '),
                    leading: CircleAvatar(
                      backgroundColor: AppColors.mist,
                      child: Icon(
                        (m['gender']?.toString() == 'Female')
                            ? Icons.woman_rounded
                            : Icons.man_rounded,
                        color: AppColors.forest,
                        size: 20,
                      ),
                    ),
                  ),
                );
              }),
            ],
          );
        },
      );
    },
  );
}

class _FlagChip extends StatelessWidget {
  const _FlagChip({required this.label, required this.color});

  final String label;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        label,
        style: TextStyle(color: color, fontSize: 11, fontWeight: FontWeight.w700),
      ),
    );
  }
}

class _HouseholdsScreenState extends State<HouseholdsScreen> {
  List<dynamic> _rows = [];
  bool _loading = true;
  String? _error;
  final _searchCtrl = TextEditingController();
  String? _purokFilter;
  String _focus = 'all';

  @override
  void initState() {
    super.initState();
    _load();
    _searchCtrl.addListener(() => setState(() {}));
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    super.dispose();
  }

  List<String> get _purokOptions {
    final set = <String>{};
    for (final r in _rows) {
      if (r is Map) {
        final p = (r['purok_label'] ?? '').toString();
        if (p.isNotEmpty) set.add(p);
      }
    }
    final list = set.toList()..sort();
    return list;
  }

  List<dynamic> get _filtered {
    final q = _searchCtrl.text.trim().toLowerCase();
    return _rows.where((r) {
      if (r is! Map) return q.isEmpty;
      final purok = (r['purok_label'] ?? '').toString();
      if (_purokFilter != null && purok != _purokFilter) return false;
      if (_focus == 'pregnant' && !householdFlagYes(r['has_pregnant'])) return false;
      if (_focus == 'lactating' && !householdFlagYes(r['has_lactating'])) return false;
      if (q.isEmpty) return true;
      final head = (r['household_head'] ?? '').toString().toLowerCase();
      final last = (r['head_last_name'] ?? '').toString().toLowerCase();
      final first = (r['head_first_name'] ?? '').toString().toLowerCase();
      return head.contains(q) || last.contains(q) || first.contains(q) || purok.toLowerCase().contains(q);
    }).toList();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    if ((widget.session.activeBarangayId ?? '').isEmpty) {
      setState(() {
        _loading = false;
        _error = widget.session.barangays.isEmpty
            ? 'No barangay is assigned to this account.'
            : 'Select a barangay to continue.';
      });
      return;
    }
    try {
      final list = await widget.session.api.households();
      setState(() => _rows = list);
    } on ApiException catch (e) {
      if (await widget.session.expireIfUnauthorized(e)) return;
      if (mounted) setState(() => _error = e.message);
    } catch (_) {
      setState(() => _error = 'Failed to load households.');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _openHousehold(String surveyId) async {
    await openHouseholdRecord(context, widget.session, surveyId);
    if (mounted) await _load();
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return const Center(child: CircularProgressIndicator());
    }
    if (_error != null) {
      return AppErrorState(message: _error!, onRetry: _load);
    }

    final filtered = _filtered;
    final puroks = _purokOptions;

    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
          child: TextField(
            controller: _searchCtrl,
            decoration: InputDecoration(
              hintText: 'Search by name or purok',
              prefixIcon: const Icon(Icons.search_rounded),
              suffixIcon: _searchCtrl.text.isNotEmpty
                  ? IconButton(
                      icon: const Icon(Icons.clear_rounded),
                      onPressed: () => _searchCtrl.clear(),
                    )
                  : null,
            ),
          ),
        ),
        SizedBox(
          height: 46,
          child: ListView(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.fromLTRB(16, 10, 16, 0),
            children: [
              for (final chip in const [
                ('all', 'All'),
                ('pregnant', 'Pregnant'),
                ('lactating', 'Lactating'),
              ])
                Padding(
                  padding: const EdgeInsets.only(right: 8),
                  child: ChoiceChip(
                    label: Text(chip.$2),
                    selected: _focus == chip.$1,
                    onSelected: (_) => setState(() => _focus = chip.$1),
                  ),
                ),
            ],
          ),
        ),
        if (puroks.isNotEmpty)
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 10, 16, 0),
            child: DropdownButtonFormField<String?>(
              key: ValueKey('purok-${_purokFilter ?? 'all'}'),
              initialValue: _purokFilter,
              isExpanded: true,
              decoration: const InputDecoration(labelText: 'Purok'),
              items: [
                const DropdownMenuItem<String?>(value: null, child: Text('All puroks')),
                ...puroks.map((p) => DropdownMenuItem<String?>(value: p, child: Text(p))),
              ],
              onChanged: (v) => setState(() => _purokFilter = v),
            ),
          ),
        Padding(
          padding: const EdgeInsets.fromLTRB(20, 14, 20, 6),
          child: Row(
            children: [
              Text(
                '${filtered.length} household${filtered.length == 1 ? '' : 's'}',
                style: Theme.of(context).textTheme.labelLarge?.copyWith(
                      color: AppColors.muted,
                      fontWeight: FontWeight.w700,
                    ),
              ),
            ],
          ),
        ),
        Expanded(
          child: RefreshIndicator(
            color: AppColors.forest,
            onRefresh: _load,
            child: _rows.isEmpty
                ? ListView(
                    children: const [
                      SizedBox(height: 40),
                      AppEmptyState(
                        icon: Icons.home_work_outlined,
                        title: 'No households yet',
                        message: 'Saved surveys appear here after you encode.',
                      ),
                    ],
                  )
                : filtered.isEmpty
                    ? ListView(
                        children: const [
                          SizedBox(height: 40),
                          AppEmptyState(
                            icon: Icons.search_off_rounded,
                            title: 'No matches',
                            message: 'Try another name or clear the purok filter.',
                          ),
                        ],
                      )
                    : ListView.separated(
                        padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
                        itemCount: filtered.length,
                        separatorBuilder: (_, __) => const SizedBox(height: 8),
                        itemBuilder: (context, i) {
                          final r = filtered[i];
                          if (r is! Map) return Text('$r');
                          final head = (r['household_head'] ?? 'Household').toString();
                          final purok = (r['purok_label'] ?? '').toString();
                          final kids = r['children_count'] ?? 0;
                          final members = r['members_count'] ?? 0;
                          final id = (r['survey_id'] ?? '').toString();
                          final flags = [
                            if (householdFlagYes(r['has_pregnant'])) 'Pregnant',
                            if (householdFlagYes(r['has_lactating'])) 'Lactating',
                            if (householdFlagYes(r['moved_out'])) 'Moved out',
                          ].join(' · ');
                          return AppListCard(
                            title: head,
                            subtitle: [
                              if (purok.isNotEmpty) purok,
                              '$members member(s) · $kids child(ren)',
                              if (flags.isNotEmpty) flags,
                              r['survey_date'] ?? '',
                            ].where((e) => '$e'.isNotEmpty).join('\n'),
                            leading: CircleAvatar(
                              backgroundColor: AppColors.mist,
                              child: Text(
                                head.isNotEmpty ? head[0].toUpperCase() : '?',
                                style: const TextStyle(
                                  color: AppColors.forest,
                                  fontWeight: FontWeight.w800,
                                ),
                              ),
                            ),
                            trailing: const Icon(Icons.chevron_right_rounded, color: AppColors.muted),
                            onTap: id.isEmpty ? null : () => _openHousehold(id),
                          );
                        },
                      ),
          ),
        ),
      ],
    );
  }
}
