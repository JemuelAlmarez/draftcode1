// Sales Data Management System
// This file handles sales data storage and retrieval across all modules

class SalesDataManager {
    constructor() {
        this.storageKey = 'galitDigitalSalesData';
        this.clerkStorageKey = 'clerkSalesData';
        this.pendingOrdersKey = 'pendingOrdersData';
    }

    // Get all sales data
    getAllSales() {
        return JSON.parse(localStorage.getItem(this.storageKey)) || [];
    }

    // Get clerk sales data
    getClerkSales() {
        return JSON.parse(localStorage.getItem(this.clerkStorageKey)) || [];
    }

    // Add a new sale
    addSale(saleData) {
        const sales = this.getAllSales();
        const sale = {
            id: Date.now(),
            ...saleData,
            timestamp: new Date().toISOString(),
            date: new Date().toDateString()
        };
        sales.push(sale);
        localStorage.setItem(this.storageKey, JSON.stringify(sales));
        return sale;
    }

    // Add clerk sale (Print & Xerox)
    addClerkSale(saleData) {
        const sales = this.getClerkSales();
        const sale = {
            id: Date.now(),
            ...saleData,
            timestamp: new Date().toISOString(),
            date: new Date().toDateString()
        };
        sales.push(sale);
        localStorage.setItem(this.clerkStorageKey, JSON.stringify(sales));
        return sale;
    }

    // Delete clerk sale (Print & Xerox)
    deleteClerkSale(saleId) {
        const sales = this.getClerkSales();
        const filteredSales = sales.filter(sale => sale.id !== saleId);
        localStorage.setItem(this.clerkStorageKey, JSON.stringify(filteredSales));
        return true;
    }

    // Pending Orders Management
    getPendingOrders() {
        return JSON.parse(localStorage.getItem(this.pendingOrdersKey)) || [];
    }

    addPendingOrder(orderData) {
        const orders = this.getPendingOrders();
        orders.push(orderData);
        localStorage.setItem(this.pendingOrdersKey, JSON.stringify(orders));
        return orderData;
    }

    updatePendingOrder(orderId, updates) {
        const orders = this.getPendingOrders();
        const orderIndex = orders.findIndex(order => order.order_id === orderId);
        if (orderIndex !== -1) {
            orders[orderIndex] = { ...orders[orderIndex], ...updates };
            localStorage.setItem(this.pendingOrdersKey, JSON.stringify(orders));
            return orders[orderIndex];
        }
        return null;
    }

    approvePendingOrder(orderId, clerkPrice, clerkNotes) {
        const order = this.updatePendingOrder(orderId, {
            clerk_approved: true,
            clerk_price: clerkPrice,
            clerk_notes: clerkNotes,
            status: 'approved',
            approved_timestamp: new Date().toISOString()
        });

        if (order) {
            // Move to regular sales
            const saleData = {
                category: order.service_category.toLowerCase().replace(' & ', ''),
                service: order.service_name,
                amount: clerkPrice,
                customer: order.customer_name,
                description: `Order ${order.order_id}: ${order.service_category}${order.service_name && order.service_name !== order.service_category ? ' - ' + order.service_name : ''}`,
                orderData: order
            };
            this.addSale(saleData);

            // Remove from pending orders
            this.removePendingOrder(orderId);
        }

        return order;
    }

    removePendingOrder(orderId) {
        const orders = this.getPendingOrders();
        const filteredOrders = orders.filter(order => order.order_id !== orderId);
        localStorage.setItem(this.pendingOrdersKey, JSON.stringify(filteredOrders));
        return true;
    }

    // Get sales by category
    getSalesByCategory(category) {
        const allSales = this.getAllSales();
        return allSales.filter(sale => sale.category === category);
    }

    // Get sales by date range
    getSalesByDateRange(startDate, endDate) {
        const allSales = this.getAllSales();
        const start = new Date(startDate);
        const end = new Date(endDate);
        
        return allSales.filter(sale => {
            const saleDate = new Date(sale.timestamp);
            return saleDate >= start && saleDate <= end;
        });
    }

    // Get today's sales
    getTodaySales() {
        const today = new Date().toDateString();
        const allSales = this.getAllSales();
        const clerkSales = this.getClerkSales();
        
        return {
            regularSales: allSales.filter(sale => sale.date === today),
            clerkSales: clerkSales.filter(sale => sale.date === today)
        };
    }

    // Get sales summary by category
    getCategorySummary() {
        const allSales = this.getAllSales();
        const clerkSales = this.getClerkSales();
        
        const summary = {
            tarpaulin: 0,
            stickers: 0,
            shirts: 0,
            printXerox: 0,
            others: 0
        };

        // Process regular sales
        allSales.forEach(sale => {
            if (summary.hasOwnProperty(sale.category)) {
                summary[sale.category] += sale.amount;
            } else {
                summary.others += sale.amount;
            }
        });

        // Process clerk sales (Print & Xerox)
        clerkSales.forEach(sale => {
            summary.printXerox += sale.amount;
        });

        return summary;
    }

    // Get detailed breakdown of "Others" category services
    getOthersBreakdown() {
        const allSales = this.getAllSales();
        
        // Define all services under "Others" category
        const othersServices = [
            'Reflective Signage', 'Panaflex Signage', 'Vehicle Decals', 'Acrylic Plates',
            'Plaque', 'Personalize Mugs', 'Printer Repair', 'PVC ID', 'Event Sash',
            'Sintra Frames', 'Standees', 'SubliUniforms', 'Fridge Magnets',
            'Sintraboard Standee', 'ID Lace', 'Steel Plate Signage', 'Acrylic Medal',
            'Acrylic Signage', 'Sintraboard Door Labels', 'Calling Card',
            'Acrylic Table Names', 'Photo Frames', 'Wood Engraving', 'Giveaways',
            'Blackout', 'Neon LED', 'DTF', 'Decals'
        ];

        const breakdown = {};
        
        // Initialize all services with 0 sales
        othersServices.forEach(service => {
            breakdown[service] = 0;
        });

        // Process sales that fall under "Others" category
        allSales.forEach(sale => {
            if (sale.category === 'others' || !['tarpaulin', 'stickers', 'shirts'].includes(sale.category)) {
                // If sale has a specific service, add to that service
                if (sale.service && othersServices.includes(sale.service)) {
                    breakdown[sale.service] += sale.amount;
                } else {
                    // If no specific service, add to "Other Services"
                    breakdown['Other Services'] = (breakdown['Other Services'] || 0) + sale.amount;
                }
            }
        });

        // Convert to array and sort by sales amount (descending)
        const sortedBreakdown = Object.entries(breakdown)
            .filter(([service, amount]) => amount > 0) // Only show services with sales
            .map(([service, amount]) => ({ service, amount }))
            .sort((a, b) => b.amount - a.amount);

        return sortedBreakdown;
    }

    // Clear all sales data (for testing purposes)
    clearAllData() {
        localStorage.removeItem(this.storageKey);
        localStorage.removeItem(this.clerkStorageKey);
    }

    // Export sales data
    exportSalesData() {
        const data = {
            regularSales: this.getAllSales(),
            clerkSales: this.getClerkSales(),
            exportDate: new Date().toISOString()
        };
        
        const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `sales-data-${new Date().toISOString().split('T')[0]}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
}

// Create global instance
window.salesDataManager = new SalesDataManager();
