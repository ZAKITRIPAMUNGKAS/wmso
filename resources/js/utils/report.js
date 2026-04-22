/**
 * Format tanggal ke "22 Apr 2026"
 * @param {string|Date} dateStr
 */
export const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    const d = new Date(dateStr);
    // Hindari timezone shift dengan parse manual
    const [year, month, day] = String(dateStr).split('T')[0].split('-').map(Number);
    return `${day} ${months[month - 1]} ${year}`;
};

/**
 * Format angka ke "Rp 1.200.000"
 * @param {number} amount
 */
export const formatRupiah = (amount) => {
    if (amount === null || amount === undefined) return 'Rp 0';
    return 'Rp ' + Number(amount).toLocaleString('id-ID');
};

/**
 * Format angka dengan pemisah ribuan
 * @param {number} num
 */
export const formatNumber = (num) => {
    return Number(num || 0).toLocaleString('id-ID');
};

/**
 * Singkat nilai rupiah untuk display ringkas (1.2M, 240K)
 * @param {number} amount
 */
export const formatRupiahShort = (amount) => {
    if (!amount) return 'Rp 0';
    if (amount >= 1_000_000_000) return `Rp ${(amount / 1_000_000_000).toFixed(1)}M`;
    if (amount >= 1_000_000)     return `Rp ${(amount / 1_000_000).toFixed(1)}Jt`;
    if (amount >= 1_000)         return `Rp ${(amount / 1_000).toFixed(0)}Rb`;
    return `Rp ${amount}`;
};
