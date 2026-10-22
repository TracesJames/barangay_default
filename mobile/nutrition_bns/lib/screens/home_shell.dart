import 'package:flutter/material.dart';

import '../api/nutrition_api.dart';
import '../offline/survey_queue.dart';
import '../session.dart';
import '../theme/app_theme.dart';
import '../widgets/app_ui.dart';
import 'change_password_screen.dart';
import 'dashboard_screen.dart';
import 'households_screen.dart';
import 'offline_queue_screen.dart';
import 'reports_screen.dart';
import 'survey_screen.dart';

class HomeShell extends StatefulWidget {
  const HomeShell({super.key, required this.session, required this.onChanged});

  final Session session;
  final VoidCallback onChanged;

  @override
  State<HomeShell> createState() => _HomeShellState();
}

class _HomeShellState extends State<HomeShell> {
  int _index = 0;
  int _pending = 0;
  bool _resolvingBarangay = true;
  final _queue = SurveyQueue();
  final _barangaySearchCtrl = TextEditingController();

  bool get _canEdit {
    final u = widget.session.user;
    if (u == null) return false;
    return u['can_edit_surveys'] == true;
  }

  @override
  void initState() {
    super.initState();
    _refreshPending();
    WidgetsBinding.instance.addPostFrameCallback((_) => _ensureBarangay());
  }

  @override
  void dispose() {
    _barangaySearchCtrl.dispose();
    super.dispose();
  }

  Future<void> _ensureBarangay() async {
    await widget.session.ensureActiveBarangay();
    if (!mounted) return;
    setState(() => _resolvingBarangay = false);
    widget.onChanged();
  }

  Future<void> _refreshPending() async {
    final list = await _queue.list();
    if (mounted) setState(() => _pending = list.length);
  }

  void _openOfflineQueue() {
    Navigator.of(context)
        .push<void>(
      MaterialPageRoute(
        builder: (_) => OfflineQueueScreen(
          session: widget.session,
          onChanged: _refreshPending,
        ),
      ),
    )
        .then((_) => _refreshPending());
  }

  Future<void> _pickBarangay() async {
    final list = widget.session.barangays;
    if (list.isEmpty) {
      final fetched = await widget.session.api.barangays();
      widget.session.barangays = fetched;
    }
    if (!mounted) return;
    final chosen = await showModalBottomSheet<String>(
      context: context,
      showDragHandle: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(22)),
      ),
      builder: (ctx) {
        final items = widget.session.barangays;
        return SafeArea(
          child: ListView(
            shrinkWrap: true,
            children: [
              const Padding(
                padding: EdgeInsets.fromLTRB(20, 4, 20, 12),
                child: Text(
                  'Select barangay',
                  style: TextStyle(fontWeight: FontWeight.w800, fontSize: 18),
                ),
              ),
              ...items.map((b) {
                final id = b is Map ? b['id']?.toString() ?? '' : '';
                final name = b is Map
                    ? (b['name'] ?? b['barangay_name'] ?? id).toString()
                    : b.toString();
                final selected = id == widget.session.activeBarangayId;
                return ListTile(
                  leading: Icon(
                    selected ? Icons.location_on : Icons.location_on_outlined,
                    color: selected ? AppColors.forest : AppColors.muted,
                  ),
                  title: Text(
                    name,
                    style: TextStyle(
                      fontWeight: selected ? FontWeight.w700 : FontWeight.w500,
                    ),
                  ),
                  trailing: selected
                      ? const Icon(Icons.check_circle, color: AppColors.forest)
                      : null,
                  onTap: () => Navigator.pop(ctx, id),
                );
              }),
              const SizedBox(height: 8),
            ],
          ),
        );
      },
    );
    if (chosen == null || chosen.isEmpty) return;
    await _selectBarangay(chosen);
  }

  void _openChangePassword() {
    Navigator.of(context).push<void>(
      MaterialPageRoute(
        builder: (_) => ChangePasswordScreen(session: widget.session),
      ),
    );
  }

  Future<void> _logout() async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Sign out?'),
        content: const Text('You can sign back in anytime with your Nutrition Portal account.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
          FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Sign out')),
        ],
      ),
    );
    if (ok != true) return;
    await widget.session.logout();
    widget.onChanged();
  }

  Future<void> _openAccountMenu() async {
    final choice = await showModalBottomSheet<String>(
      context: context,
      showDragHandle: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(22)),
      ),
      builder: (ctx) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(Icons.lock_outline_rounded, color: AppColors.forest),
              title: const Text('Change password'),
              onTap: () => Navigator.pop(ctx, 'password'),
            ),
            ListTile(
              leading: const Icon(Icons.cloud_upload_outlined, color: AppColors.forest),
              title: Text(_pending > 0 ? 'Offline queue ($_pending)' : 'Offline queue'),
              onTap: () => Navigator.pop(ctx, 'offline'),
            ),
            ListTile(
              leading: const Icon(Icons.place_outlined, color: AppColors.forest),
              title: const Text('Change barangay'),
              onTap: () => Navigator.pop(ctx, 'barangay'),
            ),
            ListTile(
              leading: Icon(Icons.logout_rounded, color: Theme.of(ctx).colorScheme.error),
              title: Text('Sign out', style: TextStyle(color: Theme.of(ctx).colorScheme.error)),
              onTap: () => Navigator.pop(ctx, 'logout'),
            ),
            const SizedBox(height: 8),
          ],
        ),
      ),
    );
    if (choice == 'password') _openChangePassword();
    if (choice == 'offline') _openOfflineQueue();
    if (choice == 'barangay') _pickBarangay();
    if (choice == 'logout') _logout();
  }

  bool get _hasBarangay => (widget.session.activeBarangayId ?? '').isNotEmpty;

  Widget _chooseBarangayBody() {
    final query = _barangaySearchCtrl.text.trim().toLowerCase();
    final items = widget.session.barangays.where((b) {
      if (query.isEmpty) return true;
      final name = b is Map
          ? (b['name'] ?? b['barangay_name'] ?? '').toString().toLowerCase()
          : b.toString().toLowerCase();
      return name.contains(query);
    }).toList();

    if (widget.session.barangays.isEmpty) {
      return const AppEmptyState(
        icon: Icons.location_off_outlined,
        title: 'No barangay assigned',
        message: 'Ask an administrator to assign a barangay to this account.',
      );
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
          child: Text(
            'Select a barangay',
            style: Theme.of(context).textTheme.titleLarge?.copyWith(
                  fontWeight: FontWeight.w800,
                ),
          ),
        ),
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
          child: Text(
            'This account covers more than one barangay. Choose which one to open.',
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                  color: AppColors.muted,
                ),
          ),
        ),
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
          child: TextField(
            controller: _barangaySearchCtrl,
            decoration: const InputDecoration(
              hintText: 'Search barangay',
              prefixIcon: Icon(Icons.search),
            ),
            onChanged: (_) => setState(() {}),
          ),
        ),
        Expanded(
          child: ListView.builder(
            itemCount: items.length,
            itemBuilder: (context, i) {
              final b = items[i];
              final id = b is Map ? b['id']?.toString() ?? '' : '';
              final name = b is Map
                  ? (b['name'] ?? b['barangay_name'] ?? id).toString()
                  : b.toString();
              return ListTile(
                leading: const Icon(Icons.location_on_outlined, color: AppColors.forest),
                title: Text(name, style: const TextStyle(fontWeight: FontWeight.w600)),
                onTap: id.isEmpty ? null : () => _selectBarangay(id),
              );
            },
          ),
        ),
      ],
    );
  }

  Future<void> _selectBarangay(String id) async {
    try {
      await widget.session.selectBarangay(id);
      widget.onChanged();
      if (mounted) setState(() {});
    } on ApiException catch (e) {
      if (await widget.session.expireIfUnauthorized(e)) return;
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
    }
  }

  @override
  Widget build(BuildContext context) {
    final pages = [
      DashboardScreen(session: widget.session),
      HouseholdsScreen(session: widget.session),
      SurveyScreen(
        session: widget.session,
        canEdit: _canEdit,
        onQueueChanged: _refreshPending,
      ),
      ReportsScreen(session: widget.session),
    ];
    final brgy = widget.session.activeBarangayName ??
        widget.session.activeBarangayId ??
        'No barangay';
    final role = widget.session.user?['role_label']?.toString() ??
        widget.session.user?['role']?.toString() ??
        '';

    return Scaffold(
      appBar: AppBar(
        titleSpacing: 16,
        title: Row(
          children: [
            ClipRRect(
              borderRadius: BorderRadius.circular(10),
              child: Image.asset(
                'assets/brand/seal.png',
                width: 34,
                height: 34,
                errorBuilder: (_, __, ___) => const Icon(Icons.eco_rounded, color: AppColors.forest),
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    widget.session.displayName,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
                  ),
                  InkWell(
                    onTap: widget.session.barangays.length > 1 ? _pickBarangay : null,
                    borderRadius: BorderRadius.circular(8),
                    child: Row(
                      children: [
                        Flexible(
                          child: Text(
                            role.isEmpty ? brgy : '$brgy · $role',
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w500,
                              color: AppColors.muted,
                            ),
                          ),
                        ),
                        if (widget.session.barangays.length > 1)
                          const Icon(Icons.expand_more_rounded, size: 16, color: AppColors.muted),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
        actions: [
          if (_pending > 0)
            IconButton(
              tooltip: '$_pending offline survey(s)',
              onPressed: _openOfflineQueue,
              icon: Badge(
                isLabelVisible: true,
                label: Text('$_pending'),
                child: const Icon(Icons.cloud_upload_outlined),
              ),
            ),
          IconButton(
            tooltip: 'Account',
            onPressed: _openAccountMenu,
            icon: const Icon(Icons.more_vert_rounded),
          ),
        ],
      ),
      body: _resolvingBarangay
          ? const Center(child: CircularProgressIndicator())
          : !_hasBarangay
              ? _chooseBarangayBody()
              : AnimatedSwitcher(
                  duration: const Duration(milliseconds: 220),
                  child: KeyedSubtree(
                    key: ValueKey('$_index-${widget.session.activeBarangayId ?? ''}'),
                    child: pages[_index],
                  ),
                ),
      bottomNavigationBar: !_hasBarangay
          ? null
          : NavigationBar(
        selectedIndex: _index,
        onDestinationSelected: (i) => setState(() => _index = i),
        destinations: const [
          NavigationDestination(
            icon: Icon(Icons.dashboard_outlined),
            selectedIcon: Icon(Icons.dashboard_rounded),
            label: 'Home',
          ),
          NavigationDestination(
            icon: Icon(Icons.home_work_outlined),
            selectedIcon: Icon(Icons.home_work_rounded),
            label: 'Households',
          ),
          NavigationDestination(
            icon: Icon(Icons.edit_note_outlined),
            selectedIcon: Icon(Icons.edit_note_rounded),
            label: 'Encode',
          ),
          NavigationDestination(
            icon: Icon(Icons.summarize_outlined),
            selectedIcon: Icon(Icons.summarize_rounded),
            label: 'Reports',
          ),
        ],
      ),
    );
  }
}
