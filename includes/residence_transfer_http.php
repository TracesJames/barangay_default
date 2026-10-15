<?php

/**
 * Request handlers for resident transfers, shared by admin/ and secretary/ endpoints.
 * Callers include connection.php and their portal auth file first.
 */

require_once __DIR__ . '/residence_transfer.php';

if (!function_exists('residence_transfer_http_json')) {
    /**
     * @param array<string, mixed> $payload
     */
    function residence_transfer_http_json(array $payload, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
        exit;
    }
}

if (!function_exists('residence_transfer_http_scope')) {
    function residence_transfer_http_scope(mysqli $con, bool $json): string
    {
        $scopeId = (string) (barangay_resolve_scope_id($con) ?? '');
        if ($scopeId !== '') {
            return $scopeId;
        }
        $message = 'Open a barangay first, then manage its resident transfers.';
        if ($json) {
            residence_transfer_http_json(['error' => $message], 400);
        }
        http_response_code(400);
        exit(barangay_h($message));
    }
}

if (!function_exists('residence_transfer_http_form')) {
    /** Modal HTML for transferring one resident (POST residence_id). */
    function residence_transfer_http_form(mysqli $con): void
    {
        barangay_require_post();
        residence_transfer_ensure_schema($con);
        $userId = (string) ($_SESSION['user_id'] ?? '');
        if (!residence_transfer_can_request($con, $userId)) {
            http_response_code(403);
            exit('You do not have permission to transfer residents.');
        }

        $fromId = residence_transfer_http_scope($con, false);
        $residenceId = trim((string) ($_POST['residence_id'] ?? ''));
        $resident = $residenceId !== '' ? residence_transfer_load_resident($con, $residenceId) : null;
        if ($resident === null || (string) ($resident['status']['barangay_id'] ?? '') !== $fromId) {
            http_response_code(404);
            exit('Resident not found in this barangay.');
        }
        $pending = residence_transfer_pending_for($con, $residenceId);
        if ($pending !== null) {
            http_response_code(409);
            exit('This resident already has a pending transfer to '
                . barangay_h(residence_transfer_barangay_name($con, (string) $pending['to_barangay_id'])) . '.');
        }

        $direct = residence_transfer_can_execute_directly($con, $userId);
        $name = residence_transfer_person_name($resident['info']);
        $fromName = residence_transfer_barangay_name($con, $fromId);
        $barangays = array_filter(barangay_list_all($con), static fn ($b) => (string) $b['id'] !== $fromId);
        $members = residence_transfer_active_residents($con, $fromId, $residenceId);
        $isHead = strtoupper((string) ($resident['status']['household_head'] ?? 'NO')) === 'YES';
        $reasonSuggestions = ['Change of residence', 'Moved in with family', 'Marriage', 'Work / employment', 'Relocation / resettlement'];
        ?>
<div class="modal fade" id="residenceTransferModal" tabindex="-1" role="dialog" aria-labelledby="residenceTransferTitle" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <form class="modal-content" id="residenceTransferForm" autocomplete="off">
      <div class="modal-header bg-indigo">
        <h5 class="modal-title" id="residenceTransferTitle"><i class="fas fa-exchange-alt mr-2"></i>Transfer Resident</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="action" value="create">
        <input type="hidden" name="residence_id" value="<?= barangay_h($residenceId) ?>">
        <div class="callout callout-info py-2 mb-3">
          <div class="text-bold"><?= barangay_h($name) ?> <small class="text-muted">(<?= barangay_h($residenceId) ?>)</small></div>
          <div class="text-sm">Current barangay: <?= barangay_h($fromName) ?><?= $isHead ? ' · Household head' : '' ?></div>
        </div>

        <div class="form-row">
          <div class="form-group col-md-7">
            <label for="transferToBarangay">Destination barangay <span class="text-danger">*</span></label>
            <select class="form-control" id="transferToBarangay" name="to_barangay" required>
              <option value="">— Select barangay —</option>
              <?php foreach ($barangays as $barangay) : ?>
              <option value="<?= barangay_h((string) $barangay['id']) ?>"><?= barangay_h((string) $barangay['barangay']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group col-md-5">
            <label for="transferDate">Transfer date <span class="text-danger">*</span></label>
            <input type="date" class="form-control" id="transferDate" name="transfer_date" value="<?= barangay_h(date('Y-m-d')) ?>" max="<?= barangay_h(date('Y-m-d', strtotime('+1 year'))) ?>" required>
          </div>
        </div>

        <div class="form-group">
          <label for="transferReason">Reason <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="transferReason" name="reason" maxlength="255" list="transferReasonList" required placeholder="e.g. Change of residence">
          <datalist id="transferReasonList">
            <?php foreach ($reasonSuggestions as $suggestion) : ?>
            <option value="<?= barangay_h($suggestion) ?>">
            <?php endforeach; ?>
          </datalist>
        </div>

        <div class="form-row">
          <div class="form-group col-md-4">
            <label for="transferHouseNumber">New house no. <small class="text-muted">(optional)</small></label>
            <input type="text" class="form-control" id="transferHouseNumber" name="new_house_number" maxlength="69">
          </div>
          <div class="form-group col-md-8">
            <label for="transferStreet">New street <small class="text-muted">(optional)</small></label>
            <input type="text" class="form-control" id="transferStreet" name="new_street" maxlength="69">
          </div>
        </div>

        <div class="form-group mb-1">
          <label class="mb-1">Household members moving together <small class="text-muted">(optional)</small></label>
          <?php if ($members === []) : ?>
          <p class="text-muted text-sm mb-0">No other active residents in this barangay.</p>
          <?php else : ?>
          <input type="search" class="form-control form-control-sm mb-2" id="transferMemberFilter" placeholder="Search residents by name or number">
          <div class="border rounded px-2 py-1" id="transferMemberList" style="max-height: 200px; overflow-y: auto;">
            <?php foreach ($members as $member) : ?>
            <div class="custom-control custom-checkbox transfer-member-item" data-search="<?= barangay_h(strtolower($member['name'] . ' ' . $member['residence_id'])) ?>">
              <input type="checkbox" class="custom-control-input" id="transferMember<?= barangay_h($member['residence_id']) ?>" name="member_ids[]" value="<?= barangay_h($member['residence_id']) ?>">
              <label class="custom-control-label font-weight-normal" for="transferMember<?= barangay_h($member['residence_id']) ?>">
                <?= barangay_h($member['name']) ?> <small class="text-muted"><?= barangay_h($member['residence_id']) ?></small>
                <?php if ($member['household_head'] === 'YES') : ?><span class="badge badge-secondary">Head</span><?php endif; ?>
              </label>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
          <small class="form-text text-muted">Each person's dependents are copied with them automatically.</small>
        </div>

        <div class="alert alert-light border text-sm mt-3 mb-0">
          <strong>What happens:</strong> the current record stays in <?= barangay_h($fromName) ?> as history (archived as <em>Transferred</em>),
          and a linked record with a new resident number is created in the destination barangay. The resident keeps the same login.
          Their household nutrition survey is marked as moved out; the receiving BNS should do a new survey.
          <?php if (!$direct) : ?>
          <br><strong>This creates a request.</strong> It takes effect after the receiving barangay admin or a Super Admin approves it.
          <?php endif; ?>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary" id="residenceTransferSubmit">
          <i class="fas fa-exchange-alt mr-1"></i><?= $direct ? 'Transfer now' : 'Submit transfer request' ?>
        </button>
      </div>
    </form>
  </div>
</div>
        <?php
    }
}

if (!function_exists('residence_transfer_http_render_batches')) {
    /**
     * @param array<int, array<string, mixed>> $batches
     */
    function residence_transfer_http_render_batches(mysqli $con, string $userId, array $batches, string $mode, string $emptyText): void
    {
        if ($batches === []) {
            echo '<p class="text-muted text-sm my-3">' . barangay_h($emptyText) . '</p>';
            return;
        }
        $statusBadges = [
            RESIDENCE_TRANSFER_PENDING => 'badge-warning',
            RESIDENCE_TRANSFER_APPROVED => 'badge-success',
            RESIDENCE_TRANSFER_REJECTED => 'badge-danger',
            RESIDENCE_TRANSFER_CANCELLED => 'badge-secondary',
        ];
        ?>
<div class="table-responsive">
  <table class="table table-sm table-hover mb-0">
    <thead class="thead-light">
      <tr>
        <th>Resident(s)</th>
        <th>From → To</th>
        <th>Date</th>
        <th>Reason</th>
        <th><?= $mode === 'history' ? 'Status' : 'Requested' ?></th>
        <?php if ($mode !== 'history') : ?><th class="text-right">Action</th><?php endif; ?>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($batches as $batch) : ?>
      <tr>
        <td>
          <?php foreach ($batch['people'] as $person) : ?>
          <div>
            <?= barangay_h($person['name']) ?>
            <small class="text-muted d-block"><?= barangay_h($person['old_id']) ?><?= $person['new_id'] !== '' ? ' → ' . barangay_h($person['new_id']) : '' ?></small>
          </div>
          <?php endforeach; ?>
        </td>
        <td><?= barangay_h($batch['from_barangay']) ?> → <?= barangay_h($batch['to_barangay']) ?></td>
        <td class="text-nowrap"><?= barangay_h($batch['transfer_date']) ?></td>
        <td><?= barangay_h($batch['reason']) ?><?php if ($batch['decision_note'] !== '') : ?><small class="text-muted d-block">Note: <?= barangay_h($batch['decision_note']) ?></small><?php endif; ?></td>
        <td class="text-nowrap">
          <?php if ($mode === 'history') : ?>
          <span class="badge <?= $statusBadges[$batch['status']] ?? 'badge-light' ?>"><?= barangay_h(ucfirst($batch['status'])) ?></span>
          <small class="text-muted d-block"><?= barangay_h($batch['decided_at']) ?></small>
          <?php else : ?>
          <small><?= barangay_h($batch['requested_at']) ?></small>
          <?php endif; ?>
        </td>
        <?php if ($mode === 'incoming') : ?>
        <td class="text-right text-nowrap">
          <?php if (residence_transfer_can_decide($con, $userId, $batch['to_barangay_id'])) : ?>
          <button type="button" class="btn btn-success btn-xs residence-transfer-decide" data-action="approve" data-transfer="<?= barangay_h($batch['transfer_id']) ?>"><i class="fas fa-check mr-1"></i>Approve</button>
          <button type="button" class="btn btn-danger btn-xs residence-transfer-decide" data-action="reject" data-transfer="<?= barangay_h($batch['transfer_id']) ?>"><i class="fas fa-times mr-1"></i>Reject</button>
          <?php else : ?>
          <small class="text-muted">Awaiting barangay admin</small>
          <?php endif; ?>
        </td>
        <?php elseif ($mode === 'outgoing') : ?>
        <td class="text-right text-nowrap">
          <?php if (residence_transfer_can_cancel($con, $userId, $batch['from_barangay_id'])) : ?>
          <button type="button" class="btn btn-outline-secondary btn-xs residence-transfer-decide" data-action="cancel" data-transfer="<?= barangay_h($batch['transfer_id']) ?>"><i class="fas fa-ban mr-1"></i>Cancel</button>
          <?php endif; ?>
        </td>
        <?php endif; ?>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
        <?php
    }
}

if (!function_exists('residence_transfer_http_panel')) {
    /** Modal HTML listing incoming, outgoing and past transfers for the active barangay. */
    function residence_transfer_http_panel(mysqli $con): void
    {
        barangay_require_post();
        residence_transfer_ensure_schema($con);
        $userId = (string) ($_SESSION['user_id'] ?? '');
        if (!residence_transfer_can_view($con, $userId)) {
            http_response_code(403);
            exit('You do not have permission to view resident transfers.');
        }
        $scopeId = residence_transfer_http_scope($con, false);
        $incoming = residence_transfer_list_batches($con, $scopeId, 'incoming', RESIDENCE_TRANSFER_PENDING);
        $outgoing = residence_transfer_list_batches($con, $scopeId, 'outgoing', RESIDENCE_TRANSFER_PENDING);
        $history = residence_transfer_list_batches($con, $scopeId, 'all', null, 50);
        $barangayName = residence_transfer_barangay_name($con, $scopeId);
        ?>
<div class="modal fade" id="residenceTransfersModal" tabindex="-1" role="dialog" aria-labelledby="residenceTransfersTitle" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header bg-indigo">
        <h5 class="modal-title" id="residenceTransfersTitle"><i class="fas fa-exchange-alt mr-2"></i>Resident Transfers — <?= barangay_h($barangayName) ?></h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">
        <ul class="nav nav-tabs" role="tablist">
          <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#transfersIncoming" role="tab">Incoming <span class="badge badge-warning"><?= count($incoming) ?></span></a></li>
          <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#transfersOutgoing" role="tab">Outgoing <span class="badge badge-secondary"><?= count($outgoing) ?></span></a></li>
          <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#transfersHistory" role="tab">History</a></li>
        </ul>
        <div class="tab-content pt-2">
          <div class="tab-pane fade show active" id="transfersIncoming" role="tabpanel">
            <?php residence_transfer_http_render_batches($con, $userId, $incoming, 'incoming', 'No transfer requests waiting for this barangay.'); ?>
          </div>
          <div class="tab-pane fade" id="transfersOutgoing" role="tabpanel">
            <?php residence_transfer_http_render_batches($con, $userId, $outgoing, 'outgoing', 'No pending requests sent from this barangay.'); ?>
          </div>
          <div class="tab-pane fade" id="transfersHistory" role="tabpanel">
            <?php residence_transfer_http_render_batches($con, $userId, $history, 'history', 'No completed transfers yet.'); ?>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
        <?php
    }
}

if (!function_exists('residence_transfer_http_action')) {
    /** POST action=create|approve|reject|cancel → JSON. */
    function residence_transfer_http_action(mysqli $con): void
    {
        barangay_require_post();
        residence_transfer_ensure_schema($con);
        $userId = (string) ($_SESSION['user_id'] ?? '');
        $action = (string) ($_POST['action'] ?? '');

        try {
            if ($action === 'create') {
                $fromId = residence_transfer_http_scope($con, true);
                $memberIds = $_POST['member_ids'] ?? [];
                $result = residence_transfer_create(
                    $con,
                    $userId,
                    $fromId,
                    trim((string) ($_POST['residence_id'] ?? '')),
                    is_array($memberIds) ? array_map('strval', $memberIds) : [],
                    trim((string) ($_POST['to_barangay'] ?? '')),
                    [
                        'transfer_date' => (string) ($_POST['transfer_date'] ?? ''),
                        'reason' => (string) ($_POST['reason'] ?? ''),
                        'new_house_number' => (string) ($_POST['new_house_number'] ?? ''),
                        'new_street' => (string) ($_POST['new_street'] ?? ''),
                    ]
                );
                $people = $result['count'] === 1 ? '1 resident' : $result['count'] . ' residents';
                $message = $result['status'] === RESIDENCE_TRANSFER_APPROVED
                    ? 'Transferred ' . $people . '. New resident number(s): ' . implode(', ', $result['new_ids']) . '.'
                    : 'Transfer request for ' . $people . ' sent. It takes effect once the receiving barangay approves it.';
                residence_transfer_http_json(['ok' => true, 'status' => $result['status'], 'message' => $message, 'new_ids' => $result['new_ids']]);
            }

            $transfer = residence_transfer_load($con, trim((string) ($_POST['transfer_id'] ?? '')));
            if ($transfer === null) {
                residence_transfer_http_json(['error' => 'Transfer not found.'], 404);
            }
            $batchId = (string) ($transfer['batch_id'] !== '' ? $transfer['batch_id'] : $transfer['id']);
            $note = (string) ($_POST['note'] ?? '');

            if ($action === 'approve') {
                $newIds = residence_transfer_approve_batch($con, $userId, $batchId);
                residence_transfer_http_json([
                    'ok' => true,
                    'message' => 'Transfer approved. New resident number(s): ' . implode(', ', $newIds) . '.',
                    'new_ids' => $newIds,
                ]);
            }
            if ($action === 'reject') {
                residence_transfer_close_batch($con, $userId, $batchId, RESIDENCE_TRANSFER_REJECTED, $note);
                residence_transfer_http_json(['ok' => true, 'message' => 'Transfer request rejected.']);
            }
            if ($action === 'cancel') {
                residence_transfer_close_batch($con, $userId, $batchId, RESIDENCE_TRANSFER_CANCELLED, $note);
                residence_transfer_http_json(['ok' => true, 'message' => 'Transfer request cancelled.']);
            }

            residence_transfer_http_json(['error' => 'Unknown action.'], 400);
        } catch (DomainException $e) {
            residence_transfer_http_json(['error' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            error_log('Resident transfer failed: ' . $e->getMessage());
            residence_transfer_http_json(['error' => 'The transfer could not be completed. No changes were saved.'], 500);
        }
    }
}
