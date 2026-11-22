import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

/**
 * Pre-configured SweetAlert2 instance adhering to the platform's
 * anti-slop, clean SaaS aesthetic (Inter/Plus Jakarta, subtle borders, crisp badges).
 */
export const AppSwal = Swal.mixin({
  buttonsStyling: false,
  customClass: {
    container: '!z-[99999]',
    popup: 'rounded-2xl border border-[#e2e5e9] shadow-2xl p-6 font-sans bg-white text-[#131416]',
    title: 'text-base font-bold text-[#131416] tracking-tight',
    htmlContainer: 'text-xs text-[#5d5f60] leading-relaxed mt-1',
    confirmButton: 'inline-flex items-center justify-center px-4 py-2 rounded-xl text-xs font-semibold text-white bg-[#1d5fd6] hover:bg-[#154cb3] transition shadow-xs cursor-pointer',
    cancelButton: 'inline-flex items-center justify-center px-4 py-2 rounded-xl text-xs font-semibold text-[#5d5f60] bg-[#f8fafc] hover:bg-[#edf0f3] border border-[#e2e5e9] transition shadow-xs cursor-pointer ml-2',
    denyButton: 'inline-flex items-center justify-center px-4 py-2 rounded-xl text-xs font-semibold text-white bg-[#ef4444] hover:bg-[#dc2626] transition shadow-xs cursor-pointer ml-2',
    actions: 'flex items-center justify-end gap-2 pt-2',
    closeButton: 'text-[#5d5f60] hover:text-[#131416] transition',
  },
  backdrop: 'rgba(15, 23, 42, 0.55)',
});

/**
 * Show a sleek confirmation dialog that replaces window.confirm.
 * Resolves to boolean `true` if user confirmed, `false` otherwise.
 */
export async function showConfirm(
  title: string,
  text: string = '',
  confirmButtonText: string = 'Confirm',
  isDanger: boolean = false
): Promise<boolean> {
  const result = await AppSwal.fire({
    title,
    text,
    icon: isDanger ? 'warning' : 'question',
    showCancelButton: true,
    confirmButtonText,
    cancelButtonText: 'Cancel',
    reverseButtons: true,
    customClass: {
      container: '!z-[99999]',
      popup: 'rounded-2xl border border-[#e2e5e9] shadow-2xl p-6 font-sans bg-white text-[#131416]',
      title: 'text-base font-bold text-[#131416] tracking-tight',
      htmlContainer: 'text-xs text-[#5d5f60] leading-relaxed mt-1',
      confirmButton: isDanger
        ? 'inline-flex items-center justify-center px-4 py-2 rounded-xl text-xs font-semibold text-white bg-[#ef4444] hover:bg-[#dc2626] transition shadow-xs cursor-pointer ml-2'
        : 'inline-flex items-center justify-center px-4 py-2 rounded-xl text-xs font-semibold text-white bg-[#1d5fd6] hover:bg-[#154cb3] transition shadow-xs cursor-pointer ml-2',
      cancelButton: 'inline-flex items-center justify-center px-4 py-2 rounded-xl text-xs font-semibold text-[#5d5f60] bg-[#f8fafc] hover:bg-[#edf0f3] border border-[#e2e5e9] transition shadow-xs cursor-pointer',
      actions: 'flex items-center justify-end gap-2 pt-2',
    },
  });

  return Boolean(result.isConfirmed);
}

/**
 * Show an alert popup that replaces window.alert.
 */
export async function showAlert(
  title: string,
  text: string = '',
  icon: 'success' | 'error' | 'warning' | 'info' = 'info'
): Promise<void> {
  await AppSwal.fire({
    title,
    text,
    icon,
    confirmButtonText: 'OK',
    customClass: {
      container: '!z-[99999]',
      popup: 'rounded-2xl border border-[#e2e5e9] shadow-2xl p-6 font-sans bg-white text-[#131416]',
      title: 'text-base font-bold text-[#131416] tracking-tight',
      htmlContainer: 'text-xs text-[#5d5f60] leading-relaxed mt-1',
      confirmButton: 'inline-flex items-center justify-center px-4 py-2 rounded-xl text-xs font-semibold text-white bg-[#1d5fd6] hover:bg-[#154cb3] transition shadow-xs cursor-pointer',
      actions: 'flex items-center justify-end gap-2 pt-2',
    },
  });
}

/**
 * Lightweight floating toast notification.
 */
export function showToast(
  title: string,
  icon: 'success' | 'error' | 'warning' | 'info' = 'success'
): void {
  const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3500,
    timerProgressBar: true,
    customClass: {
      container: '!z-[99999]',
      popup: 'rounded-xl border border-[#e2e5e9] shadow-lg p-3 font-sans bg-white text-[#131416] text-xs',
      title: 'text-xs font-bold text-[#131416]',
    },
  });

  Toast.fire({
    icon,
    title,
  });
}

export default AppSwal;
