{{--
    Product Comparison Bar (Floating)
    ===================================
    Include in ALL theme layout.blade.php before </body>:
    @include('shop.partials.compare-bar', ['client' => $client, 'baseUrl' => $baseUrl, 'clean' => $clean])

    Add compare button on ALL theme product cards:
    <button onclick="addToCompare({{ $product->id }}, '{{ $product->name }}', '{{ asset('storage/'.$product->thumbnail) }}', '{{ $price }}')" ...>
        Compare
    </button>
--}}

<div id="compare-bar"
    class="fixed bottom-16 md:bottom-4 left-1/2 -translate-x-1/2 z-[999] transition-all duration-300"
    style="display: none;">
    <div class="bg-gray-900 text-white rounded-2xl shadow-2xl px-4 py-3 flex items-center gap-3 max-w-sm md:max-w-xl w-[92vw] md:w-auto border border-white/10">
        
        {{-- Compare items preview --}}
        <div id="compare-items" class="flex gap-2 flex-1 min-w-0"></div>

        {{-- Count chip --}}
        <span id="compare-count" class="shrink-0 bg-primary text-white text-xs font-black w-6 h-6 rounded-full flex items-center justify-center">0</span>

        {{-- Compare button --}}
        <a href="{{ $clean ? $baseUrl.'/compare' : route('shop.compare', $client->slug) }}"
            id="compare-go-btn"
            class="shrink-0 bg-white text-gray-900 text-xs font-bold px-4 py-2 rounded-xl hover:bg-gray-100 transition whitespace-nowrap">
            {{ ->widgets['trans_compare'] ?? 'Compare' }} →
        </a>

        {{-- Clear --}}
        <button onclick="clearCompare()" class="shrink-0 text-gray-400 hover:text-white transition text-lg leading-none">&times;</button>
    </div>
</div>

<script>
// ── Compare state from sessionStorage ────────────────────────────────────────
var COMPARE_KEY = 'compare_{{ $client->id }}';
var MAX_COMPARE = 3;

function getCompareList() {
    try { return JSON.parse(sessionStorage.getItem(COMPARE_KEY) || '[]'); } catch(e) { return []; }
}
function saveCompareList(list) {
    sessionStorage.setItem(COMPARE_KEY, JSON.stringify(list));
}

function addToCompare(id, name, img, price) {
    var list = getCompareList();
    if (list.find(p => p.id == id)) {
        removeFromCompare(id);
        return;
    }
    if (list.length >= MAX_COMPARE) {
        showToast('সর্বোচ্চ ' + MAX_COMPARE + 'টি পণ্য তুলনা করা যাবে।', 'warning');
        return;
    }
    list.push({ id: id, name: name, img: img, price: price });
    saveCompareList(list);
    renderCompareBar();
    showToast('"' + name + '" তুলনা তালিকায় যোগ হয়েছে।', 'success');
}

function removeFromCompare(id) {
    var list = getCompareList().filter(p => p.id != id);
    saveCompareList(list);
    renderCompareBar();
}

function clearCompare() {
    saveCompareList([]);
    renderCompareBar();
}

function renderCompareBar() {
    var list    = getCompareList();
    var bar     = document.getElementById('compare-bar');
    var items   = document.getElementById('compare-items');
    var count   = document.getElementById('compare-count');

    if (!bar) return;

    if (list.length === 0) {
        bar.style.display = 'none';
        return;
    }

    bar.style.display = 'flex';
    count.textContent  = list.length;

    items.innerHTML = list.map(p =>
        '<div class="flex items-center gap-1.5 bg-white/10 rounded-lg px-2 py-1">' +
        '<img src="' + p.img + '" class="w-7 h-7 object-cover rounded" onerror="this.style.display=\'none\'">' +
        '<span class="text-xs truncate max-w-[80px]">' + p.name + '</span>' +
        '<button onclick="removeFromCompare(' + p.id + ')" class="text-gray-400 hover:text-white text-sm leading-none ml-1">&times;</button>' +
        '</div>'
    ).join('');

    // Update compare URL with product IDs
    var btn = document.getElementById('compare-go-btn');
    if (btn) {
        var url = btn.href.split('?')[0];
        btn.href = url + '?ids=' + list.map(p => p.id).join(',');
    }

    // Update compare buttons state on page
    document.querySelectorAll('[data-compare-btn]').forEach(function(btn) {
        var pid = btn.dataset.compareBtn;
        var inList = list.find(p => p.id == pid);
        btn.classList.toggle('compare-active', !!inList);
        btn.title = inList ? 'তুলনা থেকে সরান' : '{{ ->widgets['trans_compare'] ?? 'Add to Compare' }}';
    });
}

function showToast(msg, type) {
    var toast = document.createElement('div');
    toast.className = 'fixed top-4 right-4 z-[1000] px-4 py-2 rounded-lg text-sm font-semibold shadow-lg text-white transition-all ' +
        (type === 'success' ? 'bg-green-500' : 'bg-orange-500');
    toast.textContent = msg;
    document.body.appendChild(toast);
    setTimeout(function() { toast.remove(); }, 2500);
}

// Initialize on load
document.addEventListener('DOMContentLoaded', renderCompareBar);
</script>
