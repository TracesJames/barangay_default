import 'package:flutter/material.dart';

import '../api/nutrition_api.dart';
import '../session.dart';
import '../theme/app_theme.dart';
import '../widgets/app_ui.dart';
import 'children_screen.dart';
import 'households_screen.dart';

class ReportsScreen extends StatefulWidget {
  const ReportsScreen({super.key, required this.session});

  final Session session;

  @override
  State<ReportsScreen> createState() => _ReportsScreenState();
}

class _ReportsScreenState extends State<ReportsScreen> {
  Map<String, dynamic>? _summary;
  List<dynamic> _rows = [];
  bool _loading = true;
  String? _error;

  static const _summaryKeys = <(String key, String label, IconData icon, Color accent)>[
    ('households', 'Households', Icons.home_work_outlined, AppColors.forest),
    ('children', 'Children', Icons.child_care_outlined, Color(0xFF1565C0)),
    ('assessed', 'Assessed', Icons.fact_check_outlined, Color(0xFF2E7D32)),
    ('pending', 'Pending', Icons.pending_outlined, Color(0xFF78909C)),
    ('at_risk', 'At risk', Icons.warning_amber_outlined, Color(0xFFEF6C00)),
    ('normal', 'Normal', Icons.check_circle_outline, Color(0xFF2E7D32)),
    ('underweight', 'Underweight', Icons.trending_down, Color(0xFFF9A825)),
    ('wasted', 'Wasted', Icons.scale_outlined, Color(0xFFEF6C00)),
    ('severely_wasted', 'Sev. wasted', Icons.error_outline, Color(0xFFC62828)),
    ('stunted', 'Stunted', Icons.height_outlined, Color(0xFF6A1B9A)),
    ('overweight', 'Overweight', Icons.monitor_weight_outlined, Color(0xFF1565C0)),
    ('obese', 'Obese', Icons.monitor_weight_outlined, Color(0xFFAD1457)),
    ('pregnant', 'Pregnant', Icons.pregnant_woman_outlined, Color(0xFFAD1457)),
    ('teenage_pregnant', 'Teen pregnant', Icons.family_restroom_outlined, Color(0xFF6A1B9A)),
  ];

  @override
  void initState() {
    super.initState();
    _load();
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
      final body = await widget.session.api.reports();
      final summary = body['summary'];
      final list = body['consolidated'];
      setState(() {
        _summary = summary is Map<String, dynamic>
            ? summary
            : Map<String, dynamic>.from(summary as Map? ?? {});
        _rows = list is List ? list : [];
      });
    } on ApiException catch (e) {
      if (await widget.session.expireIfUnauthorized(e)) return;
      if (mounted) setState(() => _error = e.message);
    } catch (_) {
      setState(() => _error = 'Failed to load reports.');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return const Center(child: CircularProgressIndicator());
    }
    if (_error != null) {
      return AppErrorState(message: _error!, onRetry: _load);
    }

    final summary = _summary ?? {};
    return RefreshIndicator(
      color: AppColors.forest,
      onRefresh: _load,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(16, 8, 16, 28),
        children: [
          const AppSectionLabel('Summary'),
          GridView.count(
            crossAxisCount: 2,
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            mainAxisSpacing: 10,
            crossAxisSpacing: 10,
            childAspectRatio: 1.45,
            children: _summaryKeys.map((e) {
              final drill = const {
                'normal',
                'underweight',
                'wasted',
                'severely_wasted',
                'stunted',
                'overweight',
                'obese',
              }.contains(e.$1);
              return AppStatCard(
                label: e.$2,
                value: '${summary[e.$1] ?? 0}',
                icon: e.$3,
                accent: e.$4,
                onTap: drill
                    ? () => Navigator.of(context).push(
                          MaterialPageRoute(
                            builder: (_) => ChildrenScreen(
                              session: widget.session,
                              status: e.$1,
                              label: e.$2,
                            ),
                          ),
                        )
                    : null,
              );
            }).toList(),
          ),
          const SizedBox(height: 22),
          const AppSectionLabel('Consolidated households'),
          if (_rows.isEmpty)
            const Padding(
              padding: EdgeInsets.only(top: 24),
              child: AppEmptyState(
                icon: Icons.summarize_outlined,
                title: 'No records yet',
                message: 'Household surveys for this barangay will list here.',
              ),
            )
          else
            ..._rows.map((r) {
              if (r is! Map) return Text('$r');
              final head = (r['household_head'] ?? 'Household').toString();
              final purok = (r['purok_label'] ?? '').toString();
              final kids = r['children_count'] ?? 0;
              final members = r['members_count'] ?? 0;
              final id = (r['survey_id'] ?? '').toString();
              final flags = [
                if (householdFlagYes(r['has_pregnant'])) 'Pregnant',
                if (householdFlagYes(r['has_lactating'])) 'Lactating',
              ].join(' · ');
              return Padding(
                padding: const EdgeInsets.only(bottom: 8),
                child: AppListCard(
                  title: head,
                  subtitle: [
                    if (purok.isNotEmpty) purok,
                    '$members member(s) · $kids child(ren)',
                    if (flags.isNotEmpty) flags,
                    r['survey_date'] ?? '',
                  ].where((e) => '$e'.isNotEmpty).join('\n'),
                  trailing: const Icon(Icons.chevron_right_rounded, color: AppColors.muted),
                  onTap: id.isEmpty
                      ? null
                      : () async {
                          await openHouseholdRecord(context, widget.session, id);
                          if (mounted) await _load();
                        },
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
                ),
              );
            }),
        ],
      ),
    );
  }
}
