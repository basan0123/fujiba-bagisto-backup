@extends('admin::layouts.master')

@section('page_title')
    {{ __('admin::app.catalog.orders.title') }}
@endsection

@section('content')
<div class="content">
    <div class="page-header">
        <div class="page-title">
            <h1>{{ __('admin::app.catalog.orders.title') }}</h1>
        </div>
    </div>

    <div class="page-content">
        <!-- Search & Filter -->
        <div class="filter-container">
            <div class="search-box">
                <input 
                    type="text" 
                    id="orderSearch" 
                    placeholder="注文番号、顧客名、メールで検索..." 
                    class="search-input"
                >
            </div>

            <div class="filter-options">
                <select id="statusFilter" class="status-select">
                    <option value="">全てのステータス</option>
                    <option value="pending">注文受付中</option>
                    <option value="processing">処理中</option>
                    <option value="shipped">配送中</option>
                    <option value="completed">配送完了</option>
                    <option value="canceled">キャンセル</option>
                </select>
            </div>
        </div>

        <!-- Orders List -->
        <div class="orders-list-container">
            <!-- Desktop View -->
            <table class="orders-table desktop-view">
                <thead>
                    <tr>
                        <th>注文番号</th>
                        <th>顧客名</th>
                        <th>ステータス</th>
                        <th>合計金額</th>
                        <th>配送予定日</th>
                        <th>注文日時</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody id="ordersTableBody">
                    <tr class="loading-row">
                        <td colspan="7" class="text-center">読み込み中...</td>
                    </tr>
                </tbody>
            </table>

            <!-- Mobile View -->
            <div class="orders-mobile-list mobile-view" id="ordersMobileList">
                <div class="loading-message">読み込み中...</div>
            </div>
        </div>

        <!-- Pagination -->
        <div class="pagination-container">
            <button id="prevBtn" class="pagination-btn" disabled>← 前へ</button>
            <span id="pageInfo" class="page-info">ページ 1</span>
            <button id="nextBtn" class="pagination-btn" disabled>次へ →</button>
        </div>
    </div>
</div>

<!-- Status Change Modal -->
<div id="statusModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>ステータスを変更</h2>
            <button type="button" class="close-btn">&times;</button>
        </div>
        <div class="modal-body">
            <form id="statusForm">
                <input type="hidden" id="statusOrderId">
                
                <div class="form-group">
                    <label>新しいステータス</label>
                    <select id="newStatus" required>
                        <option value="">選択してください</option>
                        <option value="pending">注文受付中</option>
                        <option value="processing">処理中</option>
                        <option value="shipped">配送中</option>
                        <option value="completed">配送完了</option>
                        <option value="canceled">キャンセル</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" id="notifyCustomer" checked>
                        顧客に通知する
                    </label>
                </div>

                <div class="form-group" id="messageGroup">
                    <label for="customMessage">カスタムメッセージ（オプション）</label>
                    <textarea id="customMessage" placeholder="顧客に送信するメッセージを入力..."></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">変更を保存</button>
                    <button type="button" class="btn btn-secondary modal-close">キャンセル</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delivery Schedule Modal -->
<div id="deliveryModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>配送日時を変更</h2>
            <button type="button" class="close-btn">&times;</button>
        </div>
        <div class="modal-body">
            <form id="deliveryForm">
                <input type="hidden" id="deliveryOrderId">
                
                <div class="form-group">
                    <label>配送日</label>
                    <select id="deliveryDate" required>
                        <option value="">選択してください</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>時間帯</label>
                    <select id="deliveryTime">
                        <option value="">指定しない</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">保存</button>
                    <button type="button" class="btn btn-secondary modal-close">キャンセル</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div id="detailsModal" class="modal">
    <div class="modal-content modal-wide">
        <div class="modal-header">
            <h2>注文詳細</h2>
            <button type="button" class="close-btn">&times;</button>
        </div>
        <div class="modal-body">
            <div id="orderDetails"></div>
        </div>
    </div>
</div>

<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    .content {
        background: #f5f5f5;
        min-height: 100vh;
        padding: 20px;
    }

    .page-header {
        background: white;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .page-title h1 {
        font-size: 28px;
        color: #333;
        font-weight: 600;
    }

    .page-content {
        background: white;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        overflow: hidden;
    }

    /* Filter Section */
    .filter-container {
        display: flex;
        gap: 12px;
        padding: 20px;
        border-bottom: 1px solid #eee;
        flex-wrap: wrap;
    }

    .search-box {
        flex: 1;
        min-width: 200px;
    }

    .search-input {
        width: 100%;
        padding: 10px 15px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.3s;
    }

    .search-input:focus {
        outline: none;
        border-color: #2196F3;
        box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
    }

    .filter-options {
        min-width: 180px;
    }

    .status-select {
        width: 100%;
        padding: 10px 15px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 14px;
        cursor: pointer;
        background: white;
    }

    /* Table View (Desktop) */
    .desktop-view {
        display: table;
        width: 100%;
        border-collapse: collapse;
    }

    .orders-table {
        width: 100%;
    }

    .orders-table thead {
        background: #f9f9f9;
    }

    .orders-table th {
        padding: 15px;
        text-align: left;
        font-weight: 600;
        color: #666;
        font-size: 14px;
        border-bottom: 2px solid #eee;
    }

    .orders-table td {
        padding: 15px;
        border-bottom: 1px solid #eee;
        font-size: 14px;
    }

    .orders-table tbody tr:hover {
        background: #fafafa;
    }

    .order-number {
        font-weight: 600;
        color: #2196F3;
        cursor: pointer;
    }

    .status-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        text-transform: capitalize;
    }

    .status-pending {
        background: #FFF3CD;
        color: #856404;
    }

    .status-processing {
        background: #CFF0FC;
        color: #055160;
    }

    .status-shipped {
        background: #D4EDDA;
        color: #155724;
    }

    .status-completed {
        background: #D4EDDA;
        color: #155724;
    }

    .status-canceled {
        background: #F8D7DA;
        color: #721C24;
    }

    .amount {
        font-weight: 600;
        color: #27AE60;
    }

    .actions {
        display: flex;
        gap: 8px;
    }

    .btn-small {
        padding: 6px 12px;
        border: 1px solid #ddd;
        background: white;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        transition: all 0.3s;
    }

    .btn-small:hover {
        background: #f0f0f0;
        border-color: #999;
    }

    .btn-small.btn-status {
        color: #2196F3;
        border-color: #2196F3;
    }

    .btn-small.btn-delivery {
        color: #FF9800;
        border-color: #FF9800;
    }

    /* Mobile View */
    .mobile-view {
        display: none;
    }

    .orders-mobile-list {
        padding: 15px;
    }

    .order-card {
        background: #f9f9f9;
        border: 1px solid #eee;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
    }

    .order-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
    }

    .order-card-number {
        font-weight: 600;
        color: #2196F3;
        font-size: 14px;
    }

    .order-card-status {
        font-size: 12px;
    }

    .order-card-row {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid #eee;
        font-size: 13px;
    }

    .order-card-row:last-child {
        border-bottom: none;
    }

    .order-card-label {
        color: #999;
        font-weight: 500;
    }

    .order-card-value {
        font-weight: 600;
        color: #333;
    }

    .order-card-actions {
        display: flex;
        gap: 10px;
        margin-top: 12px;
    }

    .order-card-actions button {
        flex: 1;
        padding: 8px;
        font-size: 12px;
        border-radius: 4px;
        border: 1px solid #ddd;
        background: white;
        cursor: pointer;
    }

    /* Pagination */
    .pagination-container {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 15px;
        padding: 20px;
        border-top: 1px solid #eee;
    }

    .pagination-btn {
        padding: 8px 16px;
        border: 1px solid #ddd;
        background: white;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.3s;
    }

    .pagination-btn:hover:not(:disabled) {
        background: #2196F3;
        color: white;
        border-color: #2196F3;
    }

    .pagination-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .page-info {
        font-size: 14px;
        color: #666;
    }

    /* Modal */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }

    .modal.active {
        display: flex;
    }

    .modal-content {
        background: white;
        border-radius: 8px;
        width: 90%;
        max-width: 500px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    }

    .modal-wide {
        max-width: 800px;
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px;
        border-bottom: 1px solid #eee;
    }

    .modal-header h2 {
        font-size: 18px;
        color: #333;
        font-weight: 600;
    }

    .close-btn {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #999;
    }

    .modal-body {
        padding: 20px;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: #333;
        font-size: 14px;
    }

    .form-group input[type="text"],
    .form-group input[type="email"],
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 14px;
        font-family: inherit;
    }

    .form-group textarea {
        resize: vertical;
        min-height: 80px;
    }

    .form-group input[type="checkbox"] {
        margin-right: 8px;
    }

    .form-actions {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        margin-top: 20px;
    }

    .btn {
        padding: 10px 20px;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s;
    }

    .btn-primary {
        background: #2196F3;
        color: white;
    }

    .btn-primary:hover {
        background: #1976D2;
    }

    .btn-secondary {
        background: #f0f0f0;
        color: #333;
        border: 1px solid #ddd;
    }

    .btn-secondary:hover {
        background: #e0e0e0;
    }

    /* Responsive */
    @media (max-width: 1024px) {
        .desktop-view {
            display: none;
        }

        .mobile-view {
            display: block;
        }

        .filter-container {
            flex-direction: column;
        }

        .search-box {
            min-width: auto;
        }

        .filter-options {
            min-width: auto;
            width: 100%;
        }

        .status-select {
            width: 100%;
        }
    }

    .loading-row .text-center,
    .loading-message {
        text-align: center;
        padding: 20px;
        color: #999;
    }

    .text-center {
        text-align: center;
    }
</style>

<script>
    const API_BASE = '/admin/api/orders';
    let currentPage = 0;
    const recordsPerPage = 20;
    let totalRecords = 0;

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        loadOrders();
        setupEventListeners();
        setupModalCloseHandlers();
    });

    function setupEventListeners() {
        document.getElementById('orderSearch').addEventListener('input', function(e) {
            currentPage = 0;
            loadOrders();
        });

        document.getElementById('statusFilter').addEventListener('change', function(e) {
            currentPage = 0;
            loadOrders();
        });

        document.getElementById('prevBtn').addEventListener('click', function() {
            if (currentPage > 0) {
                currentPage--;
                loadOrders();
            }
        });

        document.getElementById('nextBtn').addEventListener('click', function() {
            if ((currentPage + 1) * recordsPerPage < totalRecords) {
                currentPage++;
                loadOrders();
            }
        });

        document.getElementById('statusForm').addEventListener('submit', handleStatusChange);
        document.getElementById('deliveryForm').addEventListener('submit', handleDeliveryUpdate);
    }

    function setupModalCloseHandlers() {
        document.querySelectorAll('.modal-close, .close-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                this.closest('.modal').classList.remove('active');
            });
        });

        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });
    }

    function loadOrders() {
        const search = document.getElementById('orderSearch').value;
        const status = document.getElementById('statusFilter').value;
        const offset = currentPage * recordsPerPage;

        fetch(`${API_BASE}/all?q=${encodeURIComponent(search)}&status=${status}&offset=${offset}&limit=${recordsPerPage}`)
            .then(r => r.json())
            .then(data => {
                totalRecords = data.total;
                renderOrders(data.data);
                updatePagination();
            })
            .catch(err => console.error('Error loading orders:', err));
    }

    function renderOrders(orders) {
        const tableBody = document.getElementById('ordersTableBody');
        const mobileList = document.getElementById('ordersMobileList');

        if (orders.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="7" class="text-center">注文が見つかりません</td></tr>';
            mobileList.innerHTML = '<div class="loading-message">注文が見つかりません</div>';
            return;
        }

        // Desktop table
        tableBody.innerHTML = orders.map(order => `
            <tr>
                <td><span class="order-number" onclick="showOrderDetails(${order.id})">#${order.order_number}</span></td>
                <td>${escapeHtml(order.customer_name)}</td>
                <td><span class="status-badge status-${order.status}">${getStatusLabel(order.status)}</span></td>
                <td class="amount">${order.currency} ${parseFloat(order.total).toFixed(2)}</td>
                <td>${order.delivery_date ? formatDate(order.delivery_date) : '未設定'}</td>
                <td>${formatDateTime(order.order_date)}</td>
                <td>
                    <div class="actions">
                        <button class="btn-small btn-status" onclick="openStatusModal(${order.id})">ステータス</button>
                        <button class="btn-small btn-delivery" onclick="openDeliveryModal(${order.id})">配送日時</button>
                    </div>
                </td>
            </tr>
        `).join('');

        // Mobile cards
        mobileList.innerHTML = orders.map(order => `
            <div class="order-card">
                <div class="order-card-header">
                    <span class="order-card-number" onclick="showOrderDetails(${order.id})">#${order.order_number}</span>
                    <span class="order-card-status status-badge status-${order.status}">${getStatusLabel(order.status)}</span>
                </div>
                <div class="order-card-row">
                    <span class="order-card-label">顧客</span>
                    <span class="order-card-value">${escapeHtml(order.customer_name)}</span>
                </div>
                <div class="order-card-row">
                    <span class="order-card-label">合計</span>
                    <span class="order-card-value">${order.currency} ${parseFloat(order.total).toFixed(2)}</span>
                </div>
                <div class="order-card-row">
                    <span class="order-card-label">配送予定</span>
                    <span class="order-card-value">${order.delivery_date ? formatDate(order.delivery_date) : '未設定'}</span>
                </div>
                <div class="order-card-actions">
                    <button onclick="openStatusModal(${order.id})">ステータス変更</button>
                    <button onclick="openDeliveryModal(${order.id})">配送日時</button>
                </div>
            </div>
        `).join('');
    }

    function openStatusModal(orderId) {
        document.getElementById('statusOrderId').value = orderId;
        document.getElementById('newStatus').value = '';
        document.getElementById('customMessage').value = '';
        document.getElementById('statusModal').classList.add('active');
    }

    function handleStatusChange(e) {
        e.preventDefault();

        const orderId = document.getElementById('statusOrderId').value;
        const status = document.getElementById('newStatus').value;
        const notifyCustomer = document.getElementById('notifyCustomer').checked;
        const message = document.getElementById('customMessage').value;

        fetch(`${API_BASE}/update-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken()
            },
            body: JSON.stringify({
                id: orderId,
                status: status,
                notify: notifyCustomer,
                message: message
            })
        })
        .then(r => r.json())
        .then(data => {
            alert('ステータスが更新されました');
            document.getElementById('statusModal').classList.remove('active');
            loadOrders();
        })
        .catch(err => {
            alert('エラーが発生しました: ' + err.message);
        });
    }

    function openDeliveryModal(orderId) {
        document.getElementById('deliveryOrderId').value = orderId;

        fetch(`${API_BASE}/delivery-options/${orderId}`)
            .then(r => r.json())
            .then(data => {
                const dateSelect = document.getElementById('deliveryDate');
                const timeSelect = document.getElementById('deliveryTime');

                dateSelect.innerHTML = '<option value="">選択してください</option>' + 
                    data.delivery_dates.map(d => 
                        `<option value="${d.iso}">${d.formatted}</option>`
                    ).join('');

                timeSelect.innerHTML = '<option value="">指定しない</option>' + 
                    data.time_slots.map(t => 
                        `<option value="${t}">${t}</option>`
                    ).join('');

                if (data.current_date) dateSelect.value = data.current_date;
                if (data.current_time) timeSelect.value = data.current_time;

                document.getElementById('deliveryModal').classList.add('active');
            });
    }

    function handleDeliveryUpdate(e) {
        e.preventDefault();

        const orderId = document.getElementById('deliveryOrderId').value;
        const deliveryDate = document.getElementById('deliveryDate').value;
        const deliveryTime = document.getElementById('deliveryTime').value;

        fetch(`${API_BASE}/update-delivery`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken()
            },
            body: JSON.stringify({
                id: orderId,
                delivery_date: deliveryDate,
                delivery_time: deliveryTime
            })
        })
        .then(r => r.json())
        .then(data => {
            alert('配送日時が更新されました');
            document.getElementById('deliveryModal').classList.remove('active');
            loadOrders();
        })
        .catch(err => {
            alert('エラーが発生しました: ' + err.message);
        });
    }

    function showOrderDetails(orderId) {
        // Implementation for showing detailed order view
        alert('注文 #' + orderId + ' の詳細ページへ移動します');
        // window.location.href = '/admin/orders/' + orderId;
    }

    function updatePagination() {
        const totalPages = Math.ceil(totalRecords / recordsPerPage);
        document.getElementById('prevBtn').disabled = currentPage === 0;
        document.getElementById('nextBtn').disabled = (currentPage + 1) >= totalPages;
        document.getElementById('pageInfo').textContent = `ページ ${currentPage + 1} / ${Math.max(1, totalPages)}`;
    }

    function getStatusLabel(status) {
        const labels = {
            'pending': '注文受付中',
            'processing': '処理中',
            'shipped': '配送中',
            'completed': '配送完了',
            'canceled': 'キャンセル'
        };
        return labels[status] || status;
    }

    function formatDate(dateStr) {
        const date = new Date(dateStr);
        return date.toLocaleDateString('ja-JP', { year: 'numeric', month: '2-digit', day: '2-digit' });
    }

    function formatDateTime(dateStr) {
        const date = new Date(dateStr);
        return date.toLocaleDateString('ja-JP', { year: 'numeric', month: '2-digit', day: '2-digit' }) + ' ' +
               date.toLocaleTimeString('ja-JP', { hour: '2-digit', minute: '2-digit' });
    }

    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    function getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]').content;
    }
</script>
@endsection
