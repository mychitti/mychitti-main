{{-- Out-of-stock / low-stock alerts when adding inventory lines (basic & advanced bills, quotations). Threshold matches inventory dashboard (stock < 5). --}}
if (typeof window.notifyInventoryStockOnAdd !== 'function') {
    window.INVENTORY_LOW_STOCK_THRESHOLD = 5;
    window.notifyInventoryStockOnAdd = function(item) {
        if (!item || item.id === undefined || item.id === null || item.id === '') return;
        var stock = parseFloat(item.stock);
        if (isNaN(stock)) stock = 0;
        var name = String(item.item_name || 'Item');
        if (stock <= 0) {
            alert('Out of stock: "' + name + '" is out of stock. You may still add this item.');
        } else if (stock < window.INVENTORY_LOW_STOCK_THRESHOLD) {
            alert('Low stock: "' + name + '" has only ' + stock + ' unit(s) available (below ' + window.INVENTORY_LOW_STOCK_THRESHOLD + ').');
        }
    };
}
if (!window.__inventoryQtyStockAlertBound) {
    window.__inventoryQtyStockAlertBound = true;
    $(document).on('change keyup', '.item_row_inv .qty, .item_row .qty, .item_row_quote .qty', function() {
        var $row = $(this).closest('tr');
        var raw = $row.attr('data-inventory-stock');
        if (raw === undefined || raw === '') return;
        var avail = parseFloat(raw);
        if (isNaN(avail)) return;
        var qty = parseFloat($(this).val());
        if (isNaN(qty) || qty <= 0) return;
        if (qty > avail) {
            alert('Quantity exceeds available stock (' + avail + ') for this inventory item.');
        }
    });
}  
