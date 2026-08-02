<?php require_once __DIR__ . '/../src/Yournotify.php';
foreach (['sendVoice','getList','getLists','updateList','deleteList','exportList','contactSummary','createContactSession','trackBatch','aliasContact','verifyWebhook'] as $method) { if (!method_exists('Yournotify', $method)) { fwrite(STDERR, "Missing {$method}\n"); exit(1); } }
$reflection = new ReflectionMethod('Yournotify', 'normalizeEvent'); $event = $reflection->invoke(new Yournotify('test'), ['event' => 'order.completed']);
if (empty($event['event_id']) || empty($event['occurred_at'])) { fwrite(STDERR, "Event normalization failed\n"); exit(1); }
echo "PHP SDK contract OK\n";
