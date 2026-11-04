import React from 'react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

export interface TablePaginationProps {
  currentPage: number;
  totalItems: number;
  pageSize: number;
  onPageChange: (page: number) => void;
  onPageSizeChange?: (pageSize: number) => void;
  pageSizeOptions?: number[];
  itemLabel?: string;
  className?: string;
}

export const TablePagination: React.FC<TablePaginationProps> = ({
  currentPage,
  totalItems,
  pageSize,
  onPageChange,
  onPageSizeChange,
  pageSizeOptions = [10, 25, 50],
  itemLabel = 'records',
  className = '',
}) => {
  const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
  const safePage = Math.min(Math.max(1, currentPage), totalPages);

  if (totalItems <= 0) {
    return null;
  }

  const startItem = (safePage - 1) * pageSize + 1;
  const endItem = Math.min(totalItems, safePage * pageSize);

  // Generate visible page numbers with ellipsis
  const pageNumbers: (number | 'ellipsis')[] = [];
  if (totalPages <= 7) {
    for (let i = 1; i <= totalPages; i++) {
      pageNumbers.push(i);
    }
  } else {
    pageNumbers.push(1);
    if (safePage > 3) {
      pageNumbers.push('ellipsis');
    }
    const start = Math.max(2, safePage - 1);
    const end = Math.min(totalPages - 1, safePage + 1);
    for (let i = start; i <= end; i++) {
      pageNumbers.push(i);
    }
    if (safePage < totalPages - 2) {
      pageNumbers.push('ellipsis');
    }
    pageNumbers.push(totalPages);
  }

  return (
    <div
      className={`p-3.5 border-t border-[#edf0f3] bg-[#fcfdfe] flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-[#5d5f60] select-none ${className}`}
    >
      <div className="flex items-center gap-3">
        <span>
          Showing <strong className="font-semibold text-[#131416]">{startItem}</strong>–<strong className="font-semibold text-[#131416]">{endItem}</strong> of{' '}
          <strong className="font-semibold text-[#131416]">{totalItems}</strong> {itemLabel}
        </span>

        {onPageSizeChange && (
          <div className="flex items-center gap-1.5 pl-3 border-l border-[#e2e5e9]">
            <span className="text-[11px] text-[#5d5f60]">Rows:</span>
            <select
              value={pageSize}
              onChange={(e) => {
                onPageSizeChange(Number(e.target.value));
                onPageChange(1);
              }}
              className="px-2 py-0.5 rounded-lg border border-[#e2e5e9] bg-white text-xs text-[#131416] focus:outline-hidden focus:border-[#1d5fd6]"
            >
              {pageSizeOptions.map((opt) => (
                <option key={opt} value={opt}>
                  {opt}
                </option>
              ))}
            </select>
          </div>
        )}
      </div>

      <div className="flex items-center gap-1">
        <button
          type="button"
          disabled={safePage <= 1}
          onClick={() => onPageChange(safePage - 1)}
          className="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-[#e2e5e9] text-xs font-medium text-[#131416] bg-white hover:bg-[#f8fafc] disabled:opacity-40 disabled:cursor-not-allowed transition"
          title="Previous Page"
        >
          <ChevronLeft className="w-3.5 h-3.5" />
          <span className="hidden sm:inline">Prev</span>
        </button>

        <div className="flex items-center gap-1 px-1">
          {pageNumbers.map((p, idx) => {
            if (p === 'ellipsis') {
              return (
                <span key={`ellipsis-${idx}`} className="px-1.5 text-xs text-[#5d5f60]">
                  …
                </span>
              );
            }
            const isCurrent = p === safePage;
            return (
              <button
                key={p}
                type="button"
                onClick={() => onPageChange(p)}
                className={`min-w-[28px] h-7 px-2 rounded-lg text-xs font-semibold transition ${
                  isCurrent
                    ? 'bg-[#1d5fd6] text-white shadow-xs'
                    : 'border border-[#e2e5e9] bg-white text-[#131416] hover:bg-[#f8fafc]'
                }`}
              >
                {p}
              </button>
            );
          })}
        </div>

        <button
          type="button"
          disabled={safePage >= totalPages}
          onClick={() => onPageChange(safePage + 1)}
          className="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-[#e2e5e9] text-xs font-medium text-[#131416] bg-white hover:bg-[#f8fafc] disabled:opacity-40 disabled:cursor-not-allowed transition"
          title="Next Page"
        >
          <span className="hidden sm:inline">Next</span>
          <ChevronRight className="w-3.5 h-3.5" />
        </button>
      </div>
    </div>
  );
};

