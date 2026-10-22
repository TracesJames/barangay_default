import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../api/nutrition_api.dart';
import '../offline/survey_queue.dart';
import '../session.dart';
import '../theme/app_theme.dart';
import '../widgets/app_ui.dart';

class OfflineQueueScreen extends StatefulWidget {
  const OfflineQueueScreen({
    super.key,
    required this.session,
    this.onChanged,
  });

  final Session session;
  final VoidCallback? onChanged;

  @override
  State<OfflineQueueScreen> createState() => _OfflineQueueScreenState();
}

class _OfflineQueueScreenState extends State<OfflineQueueScreen> {
  final _queue = SurveyQueue();
  List<QueuedSurvey> _items = [];
  bool _loading = true;
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    _reload();
  }

  Future<void> _reload() async {
    setState(() => _loading = true);
    final list = await _queue.list();
    if (mounted) {
      setState(() {
        _items = list;
        _loading = false;
      });
    }
  }

  Future<void> _syncAll() async {
    setState(() => _busy = true);
    try {
      final (synced, failed) = await _queue.syncAll(widget.session.api);
      await _reload();
      widget.onChanged?.call();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            failed == 0
                ? 'Synced $synced survey(s).'
                : 'Synced $synced · $failed still pending.',
          ),
        ),
      );
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
      await _reload();
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _retryOne(QueuedSurvey item) async {
    setState(() => _busy = true);
    try {
      await _queue.retryOne(item.id, widget.session.api);
      await _reload();
      widget.onChanged?.call();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Survey synced.')),
      );
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
      await _reload();
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _deleteOne(QueuedSurvey item) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Remove offline survey?'),
        content: Text('Discard queued data for ${item.headLabel}?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
          FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Remove')),
        ],
      ),
    );
    if (ok != true) return;
    await _queue.remove(item.id);
    await _reload();
    widget.onChanged?.call();
  }

  @override
  Widget build(BuildContext context) {
    final dateFmt = DateFormat.yMMMd().add_jm();
    return Scaffold(
      appBar: AppBar(
        title: const Text('Offline queue'),
        actions: [
          if (_items.isNotEmpty)
            Padding(
              padding: const EdgeInsets.only(right: 8),
              child: FilledButton.tonal(
                onPressed: _busy ? null : _syncAll,
                child: const Text('Sync all'),
              ),
            ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _items.isEmpty
              ? const AppEmptyState(
                  icon: Icons.cloud_done_outlined,
                  title: 'Queue is clear',
                  message: 'Offline surveys wait here until they sync to the server.',
                )
              : ListView.separated(
                  padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
                  itemCount: _items.length,
                  separatorBuilder: (_, __) => const SizedBox(height: 8),
                  itemBuilder: (context, i) {
                    final item = _items[i];
                    final hasError = item.lastError != null && item.lastError!.isNotEmpty;
                    return AppListCard(
                      title: item.headLabel,
                      subtitle: [
                        'Queued ${dateFmt.format(item.createdAt)}',
                        if (hasError) item.lastError!,
                      ].join('\n'),
                      leading: CircleAvatar(
                        backgroundColor: hasError
                            ? Theme.of(context).colorScheme.errorContainer
                            : AppColors.mist,
                        child: Icon(
                          hasError ? Icons.sync_problem_rounded : Icons.cloud_queue_rounded,
                          color: hasError
                              ? Theme.of(context).colorScheme.error
                              : AppColors.forest,
                        ),
                      ),
                      trailing: PopupMenuButton<String>(
                        enabled: !_busy,
                        onSelected: (v) {
                          if (v == 'retry') _retryOne(item);
                          if (v == 'delete') _deleteOne(item);
                        },
                        itemBuilder: (_) => const [
                          PopupMenuItem(value: 'retry', child: Text('Retry sync')),
                          PopupMenuItem(value: 'delete', child: Text('Delete')),
                        ],
                      ),
                    );
                  },
                ),
    );
  }
}
