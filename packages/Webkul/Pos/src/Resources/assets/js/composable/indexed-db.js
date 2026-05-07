import { openDB } from 'idb';

/**
 * Tables to create in the database
 */
const tables = [
    {
        name: 'agent',
        options: { keyPath: 'id', autoIncrement: false },
    },
    {
        name: 'cart',
        options: { keyPath: 'id', autoIncrement: true },
    },
    {
        name: 'cart_customer',
        options: { keyPath: 'id', autoIncrement: false },
    },
    {
        name: 'cart_items',
        options: { keyPath: 'id', autoIncrement: true },
    },
    {
        name: 'categories',
        options: { keyPath: 'id', autoIncrement: false },
    },
    {
        name: 'configurations',
        options: { keyPath: 'id', autoIncrement: false },
    },
    {
        name: 'countries_states',
        options: { keyPath: 'id', autoIncrement: false },
    },
    {
        name: 'customers',
        options: { keyPath: 'id', autoIncrement: false },
    },
    {
        name: 'exchange_rates',
        options: { keyPath: 'id', autoIncrement: false },
    },
    {
        name: 'hold_orders',
        options: { keyPath: 'id', autoIncrement: true },
    },
    {
        name: 'locales_currencies',
        options: { keyPath: 'id', autoIncrement: false },
    },
    {
        name: 'offline_customers',
        options: { keyPath: 'id', autoIncrement: true },
    },
    {
        name: 'offline_orders',
        options: { keyPath: 'id', autoIncrement: true },
    },
    {
        name: 'orders',
        options: { keyPath: 'id', autoIncrement: false },
    },
    {
        name: 'products',
        options: { keyPath: 'id', autoIncrement: false },
    },
    {
        name: 'tax_categories',
        options: { keyPath: 'id', autoIncrement: false },
    },
    {
        name: 'agent_hardware_settings',
        options: { keyPath: 'id', autoIncrement: false },
    },
    {
        name: 'pos_layouts',
        options: { keyPath: 'id', autoIncrement: false },
    },
];

/**
 * Open the database and create the tables if they don't exist
 */
const dbPromise = openDB('pos', 4, {
    upgrade(db) {
        tables.forEach(table => {
            if (!db.objectStoreNames.contains(table.name)) {
                db.createObjectStore(table.name, table.options);
            }
        });
    },
});

/**
 * Composable function to interact with the IndexedDB
 */
export const useIndexedDB = () => {
    /**
     * Get the database instance
     */
    const getDb = async () => {
        return await dbPromise;
    };

    /**
     * Get all items from a table
     */
    const getAllItems = async (tableName) => {
        const db = await getDb();

        return await db.getAll(tableName);
    };

    /**
     * Get a single item from a table by ID
     */
    const getItem = async (tableName, id) => {
        const db = await getDb();

        return await db.get(tableName, id);
    };

    /**
     * Paginate items from a table
     */
    const paginateItems = async (tableName, page = 1, limit = 5) => {
        const db = await getDb();
        const tx = db.transaction(tableName, 'readonly');
        const store = tx.objectStore(tableName);

        const total = await store.count();
        const lastPage = Math.ceil(total / limit);
        const offset = (page - 1) * limit;

        const data = [];
        let skipped = 0;
        let collected = 0;

        let cursor = await store.openCursor(null, 'prev');

        while (
            cursor
            && collected < limit
        ) {
            if (skipped < offset) {
                skipped++;
            } else {
                data.push(cursor.value);
                collected++;
            }

            cursor = await cursor.continue();
        }

        return {
            paginatorInfo: {
                count: data.length,
                currentPage: page,
                lastPage,
                total
            },
            data
        };
    };

    /**
     * Add an item to a table
     */
    const addItem = async (tableName, item) => {
        const db = await getDb();

        return await db.add(tableName, item);
    };

    /**
     * Update an item in a table
     */
    const updateItem = async (tableName, item) => {
        const db = await getDb();

        return await db.put(tableName, item);
    };

    /**
     * Delete an item from a table
     */
    const deleteItem = async (tableName, id) => {
        const db = await getDb();

        return await db.delete(tableName, id);
    };

    /**
     * Delete all items from a table
     */
    const deleteAllItems = async (tableName) => {
        const db = await getDb();

        return await db.clear(tableName);
    }

    /**
     * Get the first record from a table
     */
    const getFirstRecord = async (tableName) => {
        const db = await getDb();

        const records = await db.getAll(tableName);

        return records?.[0] || {};
    };

    /**
     * Get the current agent
     */
    const getAgent = async () => {
        return await getFirstRecord('agent');
    };

    /**
     * Get the cart
     */
    const getCart = async () => {
        return await getFirstRecord('cart');
    };

    /**
     * Get the cart customer
     */
    const getCartCustomer = async () => {
        return await getFirstRecord('cart_customer');
    };

    return {
        getDb,
        getAllItems,
        getItem,
        paginateItems,
        addItem,
        updateItem,
        deleteItem,
        deleteAllItems,
        getAgent,
        getCart,
        getCartCustomer,
    };
}
