<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
    barangs: Array,
    metodepembayaran: Array,
    pajak: Number,
    namakasir: Array,
})

const paymentMethod = ref('');
const cart = ref([]);
const showModal = ref(false);
const showModalCart = ref(false);
const selectedProduct = ref(null);
const selectedIndex = ref(null);
const quantity = ref(1);
const note = ref('');
const printer = ref(null);
const writer = ref(null);
const printerStatus = ref('OFF');
const deviceName = ref('');
const printerStatusClass = ref('badge bg-danger');
const searchQuery = ref('');
const sortOption = ref('asc');

const filteredAndSortedProducts = computed(() => {
    let filteredProducts = props.barangs.filter(barang => {
        const query = searchQuery.value.toLowerCase();
        return (
            barang.nama.toLowerCase().includes(query) ||
            barang.kategori.toLowerCase().includes(query) ||
            barang.kode.toLowerCase().includes(query)
        );
    });

    if (sortOption.value === 'asc') {
        filteredProducts.sort((a, b) => a.nama.localeCompare(b.nama));
    } else if (sortOption.value === 'desc') {
        filteredProducts.sort((a, b) => b.nama.localeCompare(a.nama));
    } else if (sortOption.value === 'price_desc') {
        filteredProducts.sort((a, b) => a.harga_jual - b.harga_jual);
    } else if (sortOption.value === 'price_asc') {
        filteredProducts.sort((a, b) => b.harga_jual - a.harga_jual);
    }

    return filteredProducts;
});

const calculateTotalPajak = () => cart.value.reduce((sum, item) => sum + item.total_pajak, 0);
const calculateTotal = () => cart.value.reduce((sum, item) => sum + item.total_harga, 0);
const calculateSubtotal = () => cart.value.reduce((sum, item) => sum + item.total_harga_without_pajak_diskon, 0);
const calculateDiskonBarang = () => cart.value.reduce((sum, item) => sum + item.total_diskon, 0);

const openProductModal = (barang) => {
    selectedProduct.value = barang;
    quantity.value = 1;
    note.value = '';
    showModal.value = true;
};

const editProductCart = (item, index) => {
    selectedProduct.value = item;
    selectedIndex.value = index;
    quantity.value = item.quantity;
    note.value = item.note;
    showModalCart.value = true;
};

const saveCartChanges = () => {
    cart.value[selectedIndex.value].quantity = quantity.value;
    cart.value[selectedIndex.value].note = note.value;
    cart.value[selectedIndex.value].total_harga_without_pajak_diskon = quantity.value * cart.value[selectedIndex.value].harga_jual;
    cart.value[selectedIndex.value].total_pajak = cart.value[selectedIndex.value].total_harga_without_pajak_diskon * (props.pajak / 100);
    cart.value[selectedIndex.value].total_harga = (cart.value[selectedIndex.value].total_harga_without_pajak_diskon - cart.value[selectedIndex.value].total_diskon) + cart.value[selectedIndex.value].total_pajak;
    showModalCart.value = false;
};

const addToCart = () => {
    if (selectedProduct.value) {
        const totalHargaPerItem = selectedProduct.value.harga_jual;
        const totalDiskon = (selectedProduct.value.diskon <= 100) ? totalHargaPerItem * (selectedProduct.value.diskon / 100) : selectedProduct.value.diskon;
        const existingProductIndex = cart.value.findIndex(item => item.kode === selectedProduct.value.kode);
        const totalHargaSebelumDiskonPajak = totalHargaPerItem * quantity.value;
        const totalPajak = totalHargaSebelumDiskonPajak * (props.pajak / 100);
        const totalHarga = (totalHargaSebelumDiskonPajak - totalDiskon) + totalPajak;

        if (existingProductIndex !== -1) {
            cart.value[existingProductIndex].quantity += quantity.value;
            cart.value[existingProductIndex].total_harga += totalHargaSebelumDiskonPajak + totalPajak
            cart.value[existingProductIndex].total_harga_without_pajak_diskon += totalHargaSebelumDiskonPajak
            cart.value[existingProductIndex].total_diskon = totalDiskon;
            cart.value[existingProductIndex].total_pajak += totalPajak
        } else {
            cart.value.push({
                kode: selectedProduct.value.kode,
                kategori: selectedProduct.value.kategori,
                nama: selectedProduct.value.nama,
                quantity: quantity.value,
                metode_pembayaran: paymentMethod.value,
                harga_jual: totalHargaPerItem,
                harga_beli: selectedProduct.value.harga_beli,
                total_harga: totalHarga,
                total_harga_without_pajak_diskon: totalHargaSebelumDiskonPajak,
                total_diskon: totalDiskon,
                total_pajak: totalPajak,
                note: note.value,
            });
        }

        showModal.value = false;
        console.log(cart);
    }
};

// const increaseQty = (barang) => {
//     selectedProduct.value = barang;
//     if ( quantity.value <= selectedProduct?.stok) {
//         quantity.value++    
//     }
// };

const increaseQty = () => {
    if (quantity.value < selectedProduct.value?.stok) {
        quantity.value++;
    } else {
        console.log("Stok tidak cukup.");
    }
};

const decreaseQty = () => {
    if (quantity.value > 1) {
        quantity.value--;
    }
};

const removeFromCart = (index) => {
    cart.value.splice(index, 1);
};

const checkout = async () => {
    if (!paymentMethod.value) {
        alert('Silakan pilih metode pembayaran terlebih dahulu.');
        return;
    }

    if (cart.value.length === 0) {
        alert('Keranjang kosong. Silakan tambahkan produk.');
        return;
    }

    const kodeTransaksi = generateRandomString();
    const cartWithTransactionCode = cart.value.map(item => ({
        ...item,
        kode_transaksi: kodeTransaksi
    }));

    try {
        const response = await axios.post('/checkout', { cart: cartWithTransactionCode, metode_pembayaran: paymentMethod.value });

        if (response.data.success) {
            alert('Checkout berhasil!');
            window.location.reload();
        } else {
            alert('Terjadi kesalahan. Silakan coba lagi.');
        }

        cart.value = [];
    } catch (error) {
        console.error('Checkout gagal:', error);
        alert('Checkout gagal. Silakan coba lagi.');
    }
};

function generateRandomString() {
    const prefix = 'kc_';
    const randomPart = Math.random().toString(36).substring(2, 7);
    return prefix + randomPart;
}


const connect = async () => {
    if (printer.value && writer.value) {
        alert('Printer sudah terhubung!');
        return;
    }

    if (!('serial' in navigator)) {
        alert('Browser Anda tidak mendukung Web Serial API.');
        return;
    }

    try {
        const port = await navigator.serial.requestPort();
        printer.value = port;
        deviceName.value = 'Menghubungkan...';
        await printer.value.open({ baudRate: 9600 });
        writer.value = printer.value.writable.getWriter();
        printerStatus.value = 'CONNECTED';
        printerStatusClass.value = 'badge bg-primary';
    } catch (error) {
        console.error('Error saat menghubungkan ke printer:', error);
        alert('Gagal menghubungkan ke printer.');
    }
};

const print = async () => {
    if (!printer.value || !writer.value) {
        alert('Pastikan printer sudah terhubung.');
        return;
    }

    const printable = {
        Align: {
            center: (text) => '\x1B' + '\x61' + '\x31' + text,
            left: (text) => '\x1B' + '\x61' + '\x00' + text,
            right: (text) => '\x1B' + '\x61' + '\x02' + text,
            reset: () => '\x1B' + '\x61' + '\x31' + '\x1D' + '\x21' + '\x00' + '\n'.repeat(2) + '\r'
        },
        Keyboard: {
            enter: (count) => '\n'.repeat(count) + '\r',
        },
        Font: {
            normal: (text) => '\x1D' + '\x21' + '\x00' + text,
            large: (text) => '\x1D' + '\x21' + '\x11' + text,
        },
        Misc: {
            centerLine: (count) => '\x1B' + '\x61' + '\x31' + '-'.repeat(count)
        }
    };
    const texts = [
        printable.Align.reset(),
        printable.Align.center(printable.Font.large('PT. Ionbit Cafe')),
        printable.Keyboard.enter(1),
        printable.Misc.centerLine(10),
        printable.Keyboard.enter(2),
        printable.Align.left(printable.Font.normal(`ID Transaksi: ` + generateRandomString())),
        printable.Keyboard.enter(1),
        printable.Align.left(printable.Font.normal(`Kasir: ${namakasir}`)),
        printable.Keyboard.enter(2),
    ];

    cart.value.forEach((item) => {
        texts.push(printable.Align.left(printable.Font.normal(`${item.nama}`)));
        texts.push(printable.Keyboard.enter(1));
        texts.push(printable.Align.left(printable.Font.normal(`Qty ${item.quantity} x ${item.harga_jual} @ ${item.total_harga.toLocaleString()}`)));
        texts.push(printable.Keyboard.enter(1));
    });

    texts.push(
        printable.Align.left(printable.Font.normal(`Subtotal\t: Rp ${calculateSubtotal().toLocaleString()}`)),
        printable.Keyboard.enter(1),
        printable.Align.left(printable.Font.normal(`Tax\t: Rp ${calculateSubtotal().toLocaleString()}`)),
        printable.Keyboard.enter(1),
        printable.Align.left(printable.Font.normal(`Diskon\t: Rp ${calculateDiskonBarang().toLocaleString()}`)),
        printable.Keyboard.enter(1),
        printable.Align.left(printable.Font.normal(`Total\t: Rp ${calculateTotalPajak().toLocaleString()}`)),
        printable.Keyboard.enter(2),
        printable.Align.center(printable.Font.normal('Terima Kasih')),
        printable.Align.reset()
    );

    try {
        for (let text of texts) {
            const encoder = new TextEncoder();  
            const encodedText = encoder.encode(text);
            await writer.value.write(encodedText);
        }
        alert('Cetak selesai!');
    } catch (error) {
        console.error('Error saat mencetak:', error);
        alert('Gagal mencetak.');
    }

};
</script>

<template>
    <Head title="Dashboard Kasir" />

    <AuthenticatedLayout>
        <div class="flex flex-col lg:flex-row h-[calc(100vh-150px)] bg-gray-100 overflow-hidden">
            <!-- Cart Section - Modified for tablet responsiveness -->
            <div class="w-full lg:w-1/4 bg-white p-4 flex flex-col h-full rounded-b-xl">
                <div class="overflow-y-auto h-[75%]">
                    <div v-for="(item, index) in cart" :key="index"
                        class="flex items-center justify-between py-2 border-b">
                        <div class="flex-1">
                            <p class="font-medium text-sm">
                                {{ item.nama }}
                                <span v-if="item.note" class="text-sm text-gray-500 italic">Note: {{ item.note }}</span>
                            </p>
                            <p class="text-gray-600 text-sm">
                                {{ item.quantity }} x Rp {{ item.harga_jual.toLocaleString() }}
                            </p>
                        </div>
                        <p class="font-medium">Rp {{ item.total_harga_without_pajak_diskon.toLocaleString() }}</p>
                        <button @click="editProductCart(item, index)"
                            class="text-blue-500 hover:underline ml-2">Edit</button>
                        <button @click="removeFromCart(index)" class="text-red-500 hover:underline ml-2">Hapus</button>
                    </div>
                </div>
               
                <div class="mt-3 pt-2 border-t">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Metode Pembayaran</span>
                        <select v-model="paymentMethod" class="px-4 lg:px-7 py-1 border rounded-lg">
                            <option v-for="mp in metodepembayaran" :key="mp.id" :value="mp.nama_mp">
                                {{ mp.nama_mp }}
                            </option>
                        </select>
                    </div>
                    <div class="mt-2">
                        <div class="flex justify-between mb-2 text-base">
                            <span class="text-gray-600">Subtotal</span>
                            <span class="font-medium">Rp {{ calculateSubtotal().toLocaleString() }}</span>
                        </div>
                        <div class="flex justify-between mb-2 text-sm border-t pt-2">
                            <span class="text-gray-600">Total Pajak</span>
                            <span class="text-gray-600">Rp {{ calculateTotalPajak().toLocaleString() }}</span>
                        </div>
                        <div class="flex justify-between mb-2 text-sm">
                            <span class="text-gray-600">Diskon Barang</span>
                            <span class="text-gray-600">- Rp {{ calculateDiskonBarang().toLocaleString() }}</span>
                        </div>
                        <div class="flex justify-between border-t pt-2">
                            <span class="text-gray-600 text-base">Total</span>
                            <span class="text-base font-semibold">Rp {{ calculateTotal().toLocaleString() }}</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-4 gap-2 mt-4">
                    <button @click="checkout"
                        class="col-span-4 p-2 bg-blue-500 text-white font-semibold hover:bg-blue-600 rounded-xl">
                        Checkout
                    </button>
                </div>
            </div>

            <!-- Products Section - Modified for tablet responsiveness -->
            <div class="flex-1 p-3 lg:p-5">
                <div class="bg-white rounded-xl p-3 lg:p-5">
                    <div class="flex flex-col sm:flex-row items-center mb-4 space-y-3 sm:space-y-0">
                        <div class="flex items-center w-full sm:w-auto">
                            <input type="text" placeholder="Cari barang..."
                                class="border border-gray-300 rounded-xl px-4 py-2 w-full sm:w-64"
                                v-model="searchQuery" />
                        </div>
                        <div class="relative w-full sm:w-auto">
                            <select v-model="sortOption"
                                class="border border-gray-300 rounded-xl px-4 py-2 cursor-pointer pr-8 sm:ml-3 w-full sm:w-auto">
                                <option value="asc">A - Z</option>
                                <option value="desc">Z - A</option>
                                <option value="price_asc">Harga ter-tinggi</option>
                                <option value="price_desc">Harga ter-rendah</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 pr-2 overflow-y-auto" style="max-height: 63vh;">
                        <div v-for="barang in filteredAndSortedProducts" :key="barang.id"
                            class="border rounded-xl p-3 lg:p-5 cursor-pointer hover:shadow-md transition-shadow w-full flex flex-col justify-center"
                            @click="openProductModal(barang)">
                            <div class="flex items-start gap-2 lg:gap-4">
                                <div class="w-20 h-20 lg:w-24 lg:h-24 bg-gray-300 rounded-lg overflow-hidden flex-shrink-0">
                                    <img src="" alt="">
                                </div>
                                <div class="flex flex-col flex-grow">
                                    <h3 class="font-bold text-base lg:text-lg">
                                        {{ barang.nama }}
                                    </h3>
                                    <p class="text-xs lg:text-sm text-gray-600">
                                        {{ barang.kategori }} | <span class="font-bold">{{ barang.kode }}</span>
                                    </p>
                                    <p class="text-xs lg:text-sm text-gray-600">
                                        Stok: <span class="font-bold text-blue-600">{{ barang.stok }}</span>
                                        <span class="text-xs font-normal text-gray-600">{{ barang.satuan }}</span>
                                    </p>
                                    <p class="font-bold text-base lg:text-lg pt-1 text-gray-600">
                                        Rp {{ barang.harga_jual }}
                                        <span class="text-xs lg:text-sm text-blue-600 font-normal">/ {{ barang.satuan }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
                <div class="bg-white rounded-lg w-96 overflow-hidden">
                    <div class="p-4 border-b">
                        <div class="flex items-center space-x-4">
                            <div>
                                <h3 class="font-semibold text-lg">{{ selectedProduct?.nama }}</h3>
                                <p class="text-gray-600">Rp {{ selectedProduct?.harga_jual.toLocaleString() }}</p>
                                <p class="text-gray-600">Ket: {{ selectedProduct?.keterangan }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-4">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Quantity</label>
                            <div class="flex items-center space-x-4">
                                <button @click="decreaseQty" class="p-2 border rounded-lg hover:bg-gray-50"></button>
                                <span class="text-xl font-semibold jumlah">{{ quantity }}</span>
                                <button @click="increaseQty" class="p-2 border rounded-lg hover:bg-gray-50">
                                </button>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                            <textarea v-model="note"
                                class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                rows="3" placeholder="Add special instructions..."></textarea>
                        </div>
                    </div>

                    <div class="p-4 bg-gray-50 flex justify-end space-x-2">
                        <button @click="showModal = false" class="px-4 py-2 border rounded-lg hover:bg-gray-100">
                            Cancel
                        </button>
                        <button @click="addToCart"
                            class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                            Add to Cart
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>