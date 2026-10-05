<?php

namespace App\Livewire\Admin;

use App\Models\GameLocation;
use App\Models\PostArrival;
use App\Models\RfidTag;
use App\Models\TapNode;
use App\Models\Team;
use Illuminate\Support\Str;
use Livewire\Component;

/**
 * The crew's side of §6: which reader stands at which post, and which card belongs to which team.
 *
 * Without this the tap system cannot be used at all — a UID means nothing until somebody says whose
 * it is. The provisioning flow it is built around is the one a crew would actually use: tap every
 * card at a booth, come back here, and assign the UIDs that turned up. That is why unknown cards are
 * listed with a button rather than hidden as errors.
 *
 * Outpost Access remains the override. This page says whether the readers are alive; it is not
 * another way to open a post by hand.
 */
class TapNodes extends Component
{
    /** A new reader. node_id is the two bytes in the LoRa frame; the key signs its HTTP taps. */
    public string $newNodeId = '';

    public string $newNodeLabel = '';

    public string $newNodePost = '';

    /** Assigning a card: the UID being claimed, and the team claiming it. */
    public string $assignUid = '';

    public string $assignTeam = '';

    public string $newTagUid = '';

    public string $newTagTeam = '';

    public function addNode(): void
    {
        $id = (int) $this->newNodeId;

        if ($id < 1 || $id > 65535) {
            session()->flash('tap_error', 'node_id harus 1–65535 (dua byte, sama seperti di frame LoRa).');

            return;
        }

        if (TapNode::where('node_id', $id)->exists()) {
            session()->flash('tap_error', "Reader dengan node_id $id sudah terdaftar.");

            return;
        }

        $node = TapNode::create([
            'node_id' => $id,
            'label' => $this->newNodeLabel !== '' ? $this->newNodeLabel : "Booth $id",
            'game_location_id' => $this->newNodePost !== '' ? (int) $this->newNodePost : null,
            // Generated here, not typed: this is the key the provisioning sketch writes into NVS,
            // and a key somebody chose by hand is a key somebody can guess.
            'node_key' => bin2hex(random_bytes(16)),
            'is_active' => true,
        ]);

        $this->reset(['newNodeId', 'newNodeLabel', 'newNodePost']);

        session()->flash('tap_msg', "Reader {$node->node_id} dibuat. Kunci HMAC-nya ada di kolom Kunci — "
            .'tulis ke NVS node itu saat provisioning, dan jangan dipakai di dua node.');
    }

    /** Move a booth: one row here instead of re-provisioning two gateways. */
    public function setPost(int $nodeRowId, string $locationId): void
    {
        $node = TapNode::find($nodeRowId);

        if (! $node) {
            return;
        }

        $node->forceFill(['game_location_id' => $locationId !== '' ? (int) $locationId : null])->save();

        session()->flash('tap_msg', "Reader {$node->node_id} sekarang menjaga "
            .($node->gameLocation?->name ?? 'belum ada pos').'.');
    }

    public function toggleNode(int $nodeRowId): void
    {
        $node = TapNode::find($nodeRowId);

        if ($node) {
            $node->forceFill(['is_active' => ! $node->is_active])->save();
        }
    }

    /** A new key, when a node is re-provisioned or a key is suspected. */
    public function rotateKey(int $nodeRowId): void
    {
        $node = TapNode::find($nodeRowId);

        if (! $node) {
            return;
        }

        $node->forceFill(['node_key' => bin2hex(random_bytes(16))])->save();

        session()->flash('tap_msg', "Kunci reader {$node->node_id} diganti. Node itu harus di-provisioning "
            .'ulang, kalau tidak tap lewat HTTP-nya akan ditolak.');
    }

    public function deleteNode(int $nodeRowId): void
    {
        $node = TapNode::find($nodeRowId);

        if ($node) {
            // The arrivals stay: they are the audit, and they carry node_id rather than a foreign key.
            $node->delete();
            session()->flash('tap_msg', 'Reader dihapus. Riwayat tap-nya tetap tersimpan.');
        }
    }

    /** Claim a UID that has been seen but belongs to nobody — the provisioning flow. */
    public function assign(): void
    {
        $uid = RfidTag::normalise($this->assignUid);
        $teamId = (int) $this->assignTeam;

        if ($uid === '' || ! $teamId) {
            session()->flash('tap_error', 'Pilih kartu dan tim.');

            return;
        }

        $team = Team::find($teamId);

        if (! $team) {
            return;
        }

        RfidTag::updateOrCreate(['uid' => $uid], [
            'team_id' => $teamId,
            'label' => $team->name,
            'is_active' => true,
        ]);

        $this->reset(['assignUid', 'assignTeam']);
        session()->flash('tap_msg', "Kartu $uid sekarang milik {$team->name}.");
    }

    /**
     * Assign in one call, from the unknown-cards list.
     *
     * Deliberately a single server action taking both values, rather than a `$set` and a `$wire`
     * call chained in one `wire:click` expression: that shape is how two buttons on this admin have
     * already shipped dead. It also means each row acts on its own UID instead of sharing one
     * property with every other row.
     */
    public function assignTo(string $uid, string $teamId): void
    {
        $this->assignUid = $uid;
        $this->assignTeam = $teamId;
        $this->assign();
    }

    /** The same, from the typed form, for cards registered before an event. */
    public function addTag(): void
    {
        $this->assignUid = $this->newTagUid;
        $this->assignTeam = $this->newTagTeam;
        $this->assign();
        $this->reset(['newTagUid', 'newTagTeam']);
    }

    public function toggleTag(int $tagId): void
    {
        $tag = RfidTag::find($tagId);

        if ($tag) {
            $tag->forceFill(['is_active' => ! $tag->is_active])->save();
        }
    }

    public function deleteTag(int $tagId): void
    {
        RfidTag::find($tagId)?->delete();
        session()->flash('tap_msg', 'Kartu dilepas dari tim.');
    }

    public function render()
    {
        $nodes = TapNode::with('gameLocation:id,name')->orderBy('node_id')->get();

        $tags = RfidTag::with('team:id,name')->orderByRaw('team_id is null desc')->orderBy('uid')->get();

        // Cards that have tapped but belong to nobody. This is the provisioning list: tap each card
        // at a booth, then assign what turned up.
        $unknown = PostArrival::query()
            ->whereIn('outcome', [PostArrival::UNKNOWN_TAG, PostArrival::TAG_UNASSIGNED])
            ->whereNotNull('uid')
            ->where('tapped_at', '>=', now()->subHours(12))
            ->selectRaw('uid, COUNT(*) as taps, MAX(tapped_at) as last_tap, MAX(node_id) as node_id')
            ->groupBy('uid')
            ->orderByDesc('last_tap')
            ->limit(40)
            ->get()
            // A UID assigned a moment ago is no longer unknown, whatever the old arrivals say.
            ->reject(fn ($row) => RfidTag::where('uid', $row->uid)->whereNotNull('team_id')->exists())
            ->values();

        return view('livewire.admin.tap-nodes', [
            'nodes' => $nodes,
            'tags' => $tags,
            'unknown' => $unknown,
            'posts' => GameLocation::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'teams' => Team::orderBy('name')->get(['id', 'name']),
            'arrivals' => PostArrival::with(['team:id,name', 'gameLocation:id,name'])
                ->orderByDesc('id')->limit(30)->get(),
            'labels' => PostArrival::LABELS,
            'silent' => $nodes->filter(fn (TapNode $n) => $n->is_active && $n->isSilent())->count(),
            'lowBattery' => $nodes->filter(fn (TapNode $n) => $n->batteryLow())->count(),
        ]);
    }
}
