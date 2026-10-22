import 'package:flutter/material.dart';

import '../api/nutrition_api.dart';
import '../session.dart';
import '../theme/app_theme.dart';
import '../widgets/app_ui.dart';
import 'children_screen.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key, required this.session});

  final Session session;

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  Map<String, dynamic>? _totals;
  String? _error;
  bool _loading = true;

  static const _underTiles = <(String key, String label, Color color)>[
    ('underweight', 'Underweight', Color(0xFFF9A825)),
    ('wasted', 'Wasted', Color(0xFFEF6C00)),
    ('severely_wasted', 'Severely wasted', Color(0xFFC62828)),
    ('stunted', 'Stunted', Color(0xFF6A1B9A)),
  ];

  static const _overTiles = <(String key, String label, Color color)>[
    ('overweight', 'Overweight', Color(0xFF1565C0)),
    ('obese', 'Obese', Color(0xFFAD1457)),
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
    final barangayId = widget.session.activeBarangayId ?? '';
    if (barangayId.isEmpty) {
      setState(() {
        _loading = false;
        _error = widget.session.barangays.isEmpty
            ? 'No barangay is assigned to this account.'
            : 'Select a barangay to continue.';
      });
      return;
    }
    try {
      final body = await widget.session.api.dashboard();
      final totals = body['totals'];
      setState(() {
        _totals = totals is Map<String, dynamic> ? totals : <String, dynamic>{};
      });
    } on ApiException catch (e) {
      if (await widget.session.expireIfUnauthorized(e)) return;
      if (mounted) setState(() => _error = e.message);
    } catch (_) {
      setState(() => _error = 'Failed to load dashboard.');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  void _openStatus(String status, String label) {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => ChildrenScreen(
          session: widget.session,
          status: status,
          label: label,
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return const Center(child: CircularProgressIndicator());
    }
    if (_error != null) {
      return AppErrorState(message: _error!, onRetry: _load);
    }

    final totals = _totals ?? {};
    final children = _count(totals, 'children');
    final assessed = _count(totals, 'assessed');
    final pending = _count(totals, 'pending');
    final atRisk = _count(totals, 'at_risk');
    final pregnant = _count(totals, 'pregnant');
    final teenPregnant = _count(totals, 'teenage_pregnant');
    final thisMonth = _count(totals, 'this_month');
    final coverage = children > 0 ? ((assessed / children) * 100).round() : 0;

    return RefreshIndicator(
      color: AppColors.forest,
      onRefresh: _load,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(16, 4, 16, 28),
        children: [
          const AppSectionLabel('Overview'),
          _metricGrid([
            _DashMetric(
              label: 'Children',
              value: '$children',
              icon: Icons.child_care_outlined,
              color: const Color(0xFF1565C0),
            ),
            _DashMetric(
              label: 'Assessed',
              value: '$assessed',
              icon: Icons.fact_check_outlined,
              color: const Color(0xFF2E7D32),
            ),
            _DashMetric(
              label: 'Pending',
              value: '$pending',
              icon: Icons.hourglass_top_rounded,
              color: const Color(0xFF78909C),
            ),
            _DashMetric(
              label: 'At risk',
              value: '$atRisk',
              icon: Icons.warning_amber_rounded,
              color: const Color(0xFFEF6C00),
            ),
            _DashMetric(
              label: 'This month',
              value: '$thisMonth',
              icon: Icons.calendar_month_outlined,
              color: const Color(0xFF1565C0),
            ),
            _DashMetric(
              label: 'Coverage',
              value: '$coverage%',
              icon: Icons.pie_chart_outline_rounded,
              color: AppColors.forest,
            ),
          ]),
          const SizedBox(height: 18),
          const AppSectionLabel('Maternal'),
          _metricGrid([
            _DashMetric(
              label: 'Pregnant',
              value: '$pregnant',
              icon: Icons.pregnant_woman_outlined,
              color: const Color(0xFFAD1457),
            ),
            _DashMetric(
              label: 'Teenage pregnant',
              value: '$teenPregnant',
              icon: Icons.family_restroom_outlined,
              color: const Color(0xFF6A1B9A),
            ),
          ]),
          const SizedBox(height: 18),
          const AppSectionLabel('Nutritional status'),
          Text(
            'Tap a status to view the children',
            style: Theme.of(context).textTheme.bodySmall?.copyWith(color: AppColors.muted),
          ),
          const SizedBox(height: 10),
          _StatusRow(
            label: 'Normal',
            count: _count(totals, 'normal'),
            color: const Color(0xFF2E7D32),
            onTap: () => _openStatus('normal', 'Normal'),
          ),
          const SizedBox(height: 14),
          const _GroupLabel('Undernutrition'),
          _statusGrid(_underTiles, totals),
          const SizedBox(height: 14),
          const _GroupLabel('Overnutrition'),
          _statusGrid(_overTiles, totals),
          if (children == 0 && assessed == 0) ...[
            const SizedBox(height: 20),
            const AppEmptyState(
              icon: Icons.insights_outlined,
              title: 'No assessments yet',
              message: 'Encode a household survey to start filling this dashboard.',
            ),
          ],
        ],
      ),
    );
  }

  int _count(Map<String, dynamic> totals, String key) {
    final value = totals[key];
    if (value is int) return value;
    if (value is num) return value.toInt();
    return int.tryParse('$value') ?? 0;
  }

  Widget _metricGrid(List<Widget> children) {
    final rows = <Widget>[];
    for (var i = 0; i < children.length; i += 2) {
      final left = children[i];
      final right = i + 1 < children.length ? children[i + 1] : const SizedBox.shrink();
      rows.add(
        Padding(
          padding: EdgeInsets.only(top: i == 0 ? 0 : 10),
          child: IntrinsicHeight(
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Expanded(child: left),
                const SizedBox(width: 10),
                Expanded(child: right),
              ],
            ),
          ),
        ),
      );
    }
    return Column(children: rows);
  }

  Widget _statusGrid(
    List<(String key, String label, Color color)> tiles,
    Map<String, dynamic> totals,
  ) {
    return GridView.count(
      crossAxisCount: 2,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      mainAxisSpacing: 10,
      crossAxisSpacing: 10,
      childAspectRatio: 1.55,
      children: [
        for (final tile in tiles)
          _StatusCell(
            label: tile.$2,
            count: _count(totals, tile.$1),
            color: tile.$3,
            onTap: () => _openStatus(tile.$1, tile.$2),
          ),
      ],
    );
  }
}

class _GroupLabel extends StatelessWidget {
  const _GroupLabel(this.text);

  final String text;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Text(
        text,
        style: Theme.of(context).textTheme.titleSmall?.copyWith(
              fontWeight: FontWeight.w700,
              color: AppColors.ink,
            ),
      ),
    );
  }
}

class _DashMetric extends StatelessWidget {
  const _DashMetric({
    required this.label,
    required this.value,
    required this.icon,
    required this.color,
  });

  final String label;
  final String value;
  final IconData icon;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      alignment: Alignment.centerLeft,
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: color.withValues(alpha: 0.16)),
      ),
      child: Row(
        children: [
          Container(
            width: 34,
            height: 34,
            decoration: BoxDecoration(
              color: color.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Icon(icon, size: 18, color: color),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Text(
                  value,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: Theme.of(context).textTheme.titleLarge?.copyWith(
                        fontWeight: FontWeight.w800,
                        color: AppColors.ink,
                        height: 1,
                      ),
                ),
                const SizedBox(height: 4),
                Text(
                  label,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: Theme.of(context).textTheme.bodySmall?.copyWith(
                        color: AppColors.muted,
                        fontWeight: FontWeight.w600,
                        height: 1.15,
                      ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _StatusRow extends StatelessWidget {
  const _StatusRow({
    required this.label,
    required this.count,
    required this.color,
    required this.onTap,
  });

  final String label;
  final int count;
  final Color color;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(16),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: color.withValues(alpha: 0.2)),
          ),
          child: Row(
            children: [
              Container(
                width: 10,
                height: 10,
                decoration: BoxDecoration(color: color, shape: BoxShape.circle),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Text(
                  label,
                  style: const TextStyle(fontWeight: FontWeight.w700, color: AppColors.ink),
                ),
              ),
              Text(
                '$count',
                style: Theme.of(context).textTheme.titleLarge?.copyWith(
                      fontWeight: FontWeight.w800,
                      color: AppColors.ink,
                    ),
              ),
              const SizedBox(width: 4),
              const Icon(Icons.chevron_right_rounded, color: AppColors.muted),
            ],
          ),
        ),
      ),
    );
  }
}

class _StatusCell extends StatelessWidget {
  const _StatusCell({
    required this.label,
    required this.count,
    required this.color,
    required this.onTap,
  });

  final String label;
  final int count;
  final Color color;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(16),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: Container(
          padding: const EdgeInsets.fromLTRB(12, 12, 12, 10),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: color.withValues(alpha: 0.18)),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 8,
                height: 8,
                decoration: BoxDecoration(color: color, shape: BoxShape.circle),
              ),
              const Spacer(),
              Text(
                '$count',
                style: Theme.of(context).textTheme.titleLarge?.copyWith(
                      fontWeight: FontWeight.w800,
                      color: AppColors.ink,
                      height: 1,
                    ),
              ),
              const SizedBox(height: 4),
              Text(
                label,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: Theme.of(context).textTheme.bodySmall?.copyWith(
                      color: AppColors.muted,
                      fontWeight: FontWeight.w600,
                      height: 1.15,
                    ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
