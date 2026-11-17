import 'package:flutter/material.dart';

import '../api/nutrition_api.dart';
import '../session.dart';
import '../theme/app_theme.dart';
import '../widgets/app_ui.dart';
import 'survey_screen.dart';

class IncompleteHouseholdsScreen extends StatefulWidget {
  const IncompleteHouseholdsScreen({
    super.key,
    required this.session,
    this.onChanged,
  });

  final Session session;
  final VoidCallback? onChanged;

  @override
  State<IncompleteHouseholdsScreen> createState() => _IncompleteHouseholdsScreenState();
}

class _IncompleteHouseholdsScreenState extends State<IncompleteHouseholdsScreen> {
  bool _loading = true;
  String? _error;
  List<dynamic> _rows = [];

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
    try {
      final rows = await widget.session.api.incompleteHouseholds();
      if (!mounted) return;
      setState(() {
        _rows = rows;
        _loading = false;
      });
    } on ApiException catch (e) {
      if (await widget.session.expireIfUnauthorized(e)) return;
      if (!mounted) return;
      setState(() {
        _error = e.message;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.toString();
        _loading = false;
      });
    }
  }

  String _title(Map row) {
    final head = (row['household_head'] ?? '').toString().trim();
    if (head.isNotEmpty) return head;
    final last = (row['head_last_name'] ?? '').toString();
    final first = (row['head_first_name'] ?? '').toString();
    final name = '$last, $first'.replaceAll(RegExp(r'^,\s*|,\s*$'), '').trim();
    return name.isEmpty ? (row['survey_id']?.toString() ?? 'Survey') : name;
  }

  Future<void> _open(Map row) async {
    final id = row['survey_id']?.toString() ?? '';
    if (id.isEmpty) return;
    await Navigator.of(context).push<void>(
      MaterialPageRoute(
        builder: (_) => SurveyScreen(
          session: widget.session,
          surveyId: id,
          canEdit: widget.session.user?['can_edit_surveys'] == true ||
              widget.session.user?['can_add_surveys'] == true,
          onQueueChanged: widget.onChanged,
        ),
      ),
    );
    await _load();
    widget.onChanged?.call();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Incomplete households'),
        actions: [
          IconButton(
            onPressed: _loading ? null : _load,
            icon: const Icon(Icons.refresh_rounded),
          ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? AppEmptyState(
                  icon: Icons.error_outline,
                  title: 'Could not load',
                  message: _error!,
                )
              : _rows.isEmpty
                  ? const AppEmptyState(
                      icon: Icons.task_alt_rounded,
                      title: 'All caught up',
                      message: 'No incomplete households with children needing assessment.',
                    )
                  : RefreshIndicator(
                      onRefresh: _load,
                      child: ListView.separated(
                        padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
                        itemCount: _rows.length,
                        separatorBuilder: (_, __) => const SizedBox(height: 8),
                        itemBuilder: (context, i) {
                          final raw = _rows[i];
                          final row = raw is Map ? Map<String, dynamic>.from(raw) : <String, dynamic>{};
                          final issue = row['issue_label']?.toString() ?? 'Incomplete';
                          final purok = row['purok_label']?.toString() ?? '';
                          final date = row['survey_date']?.toString() ?? '';
                          return AppListCard(
                            title: _title(row),
                            subtitle: [
                              if (purok.isNotEmpty) purok,
                              if (date.isNotEmpty) date,
                              issue,
                            ].join(' · '),
                            leading: const CircleAvatar(
                              backgroundColor: AppColors.mist,
                              child: Icon(Icons.warning_amber_rounded, color: AppColors.forest),
                            ),
                            onTap: () => _open(row),
                            trailing: const Icon(Icons.chevron_right_rounded),
                          );
                        },
                      ),
                    ),
    );
  }
}
