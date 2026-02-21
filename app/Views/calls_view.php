
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Ringba Call Reports</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
</head>
<body>
<div class="container mt-5">
<h1 class="mb-4 text-center">Ringba Call Reports</h1>
<table id="callsTable" class="table table-striped table-bordered">
<thead class="table-dark">
<tr>
<th>Timestamp</th>
<th>Campaign</th>
<th>Payout</th>
<th>Tier</th>
<th>Duration (s)</th>
<th>Has Recording</th>
<th>Sentiment</th>
<th>Summary</th>
<th>Transcript URL</th>
</tr>
</thead>
<tbody>
<?php foreach($calls as $call): ?>
<tr>
<td><?= date('Y-m-d H:i:s', strtotime($call['timestamp'])) ?></td>
<td><?= $call['campaign_name'] ?></td>
<td><?= $call['payout'] ?></td>
<td><?= $call['tier'] ?></td>
<td><?= $call['duration'] ?></td>
<td><?= $call['has_recording']?'Yes':'No' ?></td>
<td>
<?php $color = match($call['sentiment']) {
    'Positive'=>'success',
    'Negative'=>'danger',
    default=>'secondary'
}; ?>
<span class="badge bg-<?= $color ?>"><?= $call['sentiment'] ?></span>
</td>
<td><?= nl2br($call['summary']) ?></td>
<td><?php if($call['transcript_url']): ?>
<a href="<?= $call['transcript_url'] ?>" target="_blank" class="btn btn-sm btn-primary">View</a>
<?php else: ?>N/A<?php endif; ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function(){
    $('#callsTable').DataTable({
        "pageLength": 25,
        "order": [[0,"desc"]],
        "columnDefs":[{"orderable":false,"targets":[7,8]}]
    });
});
</script>
</body>
</html>
