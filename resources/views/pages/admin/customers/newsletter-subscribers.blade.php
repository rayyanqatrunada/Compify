<?php

use App\Models\NewsletterSubscriber;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Layout('components.layouts.admin')]
#[Title('Newsletter Subscribers - Admin Compify')]
class extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $source = '';

    #[Url]
    public string $sort = 'latest';

    public int $perPage = 10;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSource(): void
    {
        $this->resetPage();
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function subscribers()
    {
        $search = trim($this->search);

        return NewsletterSubscriber::query()
            ->with('customer')
            ->when($search !== '', function ($query) use ($search) {
                $like = '%' . $search . '%';

                $query->where(function ($q) use ($like) {
                    $q->where('email', 'like', $like)
                        ->orWhere('source', 'like', $like)
                        ->orWhere('ip_address', 'like', $like)
                        ->orWhereHas('customer', function ($customerQuery) use ($like) {
                            $customerQuery->where('name', 'like', $like)
                                ->orWhere('email', 'like', $like)
                                ->orWhere('username', 'like', $like);
                        });
                });
            })
            ->when($this->source !== '', function ($query) {
                $query->where('source', $this->source);
            })
            ->when($this->sort === 'latest', fn ($query) => $query->latest())
            ->when($this->sort === 'oldest', fn ($query) => $query->oldest())
            ->when($this->sort === 'email_asc', fn ($query) => $query->orderBy('email'))
            ->when($this->sort === 'email_desc', fn ($query) => $query->orderByDesc('email'))
            ->paginate($this->perPage);
    }

    #[Computed]
    public function sources()
    {
        return NewsletterSubscriber::query()
            ->whereNotNull('source')
            ->where('source', '!=', '')
            ->select('source')
            ->distinct()
            ->orderBy('source')
            ->pluck('source');
    }

    #[Computed]
    public function totalSubscribers(): int
    {
        return NewsletterSubscriber::count();
    }

    #[Computed]
    public function todaySubscribers(): int
    {
        return NewsletterSubscriber::whereDate('created_at', today())->count();
    }

    #[Computed]
    public function customerSubscribers(): int
    {
        return NewsletterSubscriber::whereNotNull('customer_id')->count();
    }

    #[Computed]
    public function footerSubscribers(): int
    {
        return NewsletterSubscriber::where('source', 'footer')->count();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->source = '';
        $this->sort = 'latest';
        $this->perPage = 10;

        $this->resetPage();
    }

    public function deleteSubscriber(int $id): void
    {
        $subscriber = NewsletterSubscriber::findOrFail($id);

        $subscriber->delete();

        session()->flash('success', 'Subscriber berhasil dihapus.');
        $this->resetPage();
    }

    public function formatDate($date): string
    {
        if (! $date) {
            return '-';
        }

        return $date->format('d M Y H:i');
    }
};
?>

<div class="admin-page-v2 admin-newsletter-page-v2">
    <div class="admin-section-title-v2">
        <h2>Newsletter Subscribers</h2>
        <p>Kelola email yang masuk dari form newsletter footer dan sumber lainnya.</p>
    </div>

    @if(session('success'))
        <div class="admin-alert-v2 admin-alert-v2--success">
            {{ session('success') }}
        </div>
    @endif

    <div class="admin-newsletter-stats-v2">
        <div>
            <span>Total Subscriber</span>
            <strong>{{ number_format($this->totalSubscribers) }}</strong>
        </div>

        <div>
            <span>Hari Ini</span>
            <strong>{{ number_format($this->todaySubscribers) }}</strong>
        </div>

        <div>
            <span>Dari Customer Login</span>
            <strong>{{ number_format($this->customerSubscribers) }}</strong>
        </div>

        <div>
            <span>Dari Footer</span>
            <strong>{{ number_format($this->footerSubscribers) }}</strong>
        </div>
    </div>

    <div class="admin-panel-v2">
        <div class="admin-table-head">
            <h2>Data Subscriber</h2>

            <div class="admin-newsletter-actions-v2">
                <button type="button" class="admin-btn secondary" wire:click="resetFilters">
                    Reset Filter
                </button>
            </div>
        </div>

        <div class="admin-newsletter-filter-v2">
            <label>
                Search
                <input
                    type="text"
                    wire:model.live.debounce.400ms="search"
                    placeholder="Cari email, customer, source, atau IP..."
                >
            </label>

            <label>
                Source
                <select wire:model.live="source">
                    <option value="">Semua Source</option>

                    @foreach($this->sources as $item)
                        <option value="{{ $item }}">{{ ucfirst($item) }}</option>
                    @endforeach
                </select>
            </label>

            <label>
                Sort
                <select wire:model.live="sort">
                    <option value="latest">Terbaru</option>
                    <option value="oldest">Terlama</option>
                    <option value="email_asc">Email A-Z</option>
                    <option value="email_desc">Email Z-A</option>
                </select>
            </label>

            <label>
                Tampilkan
                <select wire:model.live="perPage">
                    <option value="10">10 data</option>
                    <option value="20">20 data</option>
                    <option value="50">50 data</option>
                    <option value="100">100 data</option>
                </select>
            </label>
        </div>

        <table class="admin-table-v2 admin-newsletter-table-v2">
            <thead>
                <tr>
                    <th>Email</th>
                    <th>Customer</th>
                    <th>Source</th>
                    <th>IP</th>
                    <th>Subscribe Date</th>
                    <th>Created</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>
                @forelse($this->subscribers as $subscriber)
                    <tr>
                        <td>
                            <strong>{{ $subscriber->email }}</strong>
                        </td>

                        <td>
                            @if($subscriber->customer)
                                <div class="admin-newsletter-customer-v2">
                                    <strong>{{ $subscriber->customer->name ?? $subscriber->customer->username }}</strong>
                                    <small>{{ $subscriber->customer->email }}</small>
                                </div>
                            @else
                                <span class="admin-newsletter-muted-v2">
                                    Guest
                                </span>
                            @endif
                        </td>

                        <td>
                            <span class="admin-newsletter-pill-v2">
                                {{ $subscriber->source ?: 'unknown' }}
                            </span>
                        </td>

                        <td>
                            <small>{{ $subscriber->ip_address ?: '-' }}</small>
                        </td>

                        <td>
                            <small>{{ $this->formatDate($subscriber->subscribed_at) }}</small>
                        </td>

                        <td>
                            <small>{{ $this->formatDate($subscriber->created_at) }}</small>
                        </td>

                        <td>
                            <button
                                type="button"
                                class="admin-btn danger"
                                wire:click="deleteSubscriber({{ $subscriber->id }})"
                                wire:confirm="Yakin hapus subscriber ini?"
                            >
                                Hapus
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            Belum ada subscriber yang cocok.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="admin-pagination">
            {{ $this->subscribers->links() }}
        </div>
    </div>
</div>