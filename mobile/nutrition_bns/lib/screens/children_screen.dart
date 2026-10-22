import 'package:flutter/material.dart';

import '../api/nutrition_api.dart';
import '../session.dart';
import '../theme/app_theme.dart';
import '../widgets/app_ui.dart';

class ChildrenScreen extends StatefulWidget {
  const ChildrenScreen({
    super.key,
    required this.session,
    required this.status,
    required this.label,
  });

  final Session session;
  final String status;
  final String label;

  @override
  State<ChildrenScreen> createState() => _ChildrenScreenState();
}

class _ChildrenScreenState extends State<ChildrenScreen> {
  List<dynamic> _rows = [];
  bool _loading = true;
  String? _error;
  final _searchCtrl = TextEditingController();

  @override
  void dispose() {
    _searchCtrl.dispose();
    super.dispose();
  }

  List<dynamic> get _filtered {
    final q = _searchCtrl.text.trim().toLowerCase();
    if (q.isEmpty) return _rows;
    return _rows.where((r) {
      if (r is! Map) return false;
      final name = (r['name'] ?? r['child_name'] ?? r['member_name'] ?? '').toString().toLowerCase();
      final purok = (r['purok'] ?? r['purok_label'] ?? '').toString().toLowerCase();
      final household = (r['household'] ?? '').toString().toLowerCase();
      return name.contains(q) || purok.contains(q) || household.contains(q);
    }).toList();
  }

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
      final list = await widget.session.api.children(status: widget.status);
      setState(() => _rows = list);
    } on ApiException catch (e) {
      if (await widget.session.expireIfUnauthorized(e)) return;
      if (mounted) setState(() => _error = e.message);
    } catch (_) {
      setState(() => _error = 'Failed to load children.');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(widget.label)),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? AppErrorState(message: _error!, onRetry: _load)
              : Column(
                  children: [
                    Padding(
                      padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
                      child: TextField(
                        controller: _searchCtrl,
                        decoration: InputDecoration(
                          hintText: 'Search name, purok, or household',
                          prefixIcon: const Icon(Icons.search_rounded),
                          suffixIcon: _searchCtrl.text.isEmpty
                              ? null
                              : IconButton(
                                  icon: const Icon(Icons.clear_rounded),
                                  onPressed: () => _searchCtrl.clear(),
                                ),
                        ),
                        onChanged: (_) => setState(() {}),
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
                                    icon: Icons.child_care_outlined,
                                    title: 'No children here',
                                    message: 'Counts update as household surveys are encoded.',
                                  ),
                                ],
                              )
                            : _filtered.isEmpty
                                ? ListView(
                                    children: const [
                                      SizedBox(height: 40),
                                      AppEmptyState(
                                        icon: Icons.search_off_rounded,
                                        title: 'No matches',
                                        message: 'Try another name or purok.',
                                      ),
                                    ],
                                  )
                                : ListView.separated(
                                    padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
                                    itemCount: _filtered.length,
                                    separatorBuilder: (_, __) => const SizedBox(height: 8),
                                    itemBuilder: (context, i) {
                                      final r = _filtered[i];
                                      if (r is! Map) return Text('$r');
                                      final name = (r['name'] ??
                                              r['child_name'] ??
                                              r['member_name'] ??
                                              'Child')
                                          .toString();
                                      final purok = (r['purok'] ?? r['purok_label'] ?? '').toString();
                                      final age = (r['age_label'] ?? r['age_months'] ?? '').toString();
                                      final date = (r['date'] ?? '').toString();
                                      final subtitle = [
                                        if (purok.isNotEmpty) purok,
                                        if (age.isNotEmpty) age,
                                        if ((r['household'] ?? '').toString().isNotEmpty)
                                          'HH: ${r['household']}',
                                        if (date.isNotEmpty) date,
                                      ].join(' · ');
                                      return AppListCard(
                                        title: name,
                                        subtitle: subtitle.isEmpty ? null : subtitle,
                                        leading: CircleAvatar(
                                          backgroundColor: AppColors.mist,
                                          child: Text(
                                            name.isNotEmpty ? name[0].toUpperCase() : '?',
                                            style: const TextStyle(
                                              color: AppColors.forest,
                                              fontWeight: FontWeight.w800,
                                            ),
                                          ),
                                        ),
                                      );
                                    },
                                  ),
                      ),
                    ),
                  ],
                ),
    );
  }
}
