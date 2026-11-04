import React, { useEffect, useState } from 'react';
import { api } from '../../api/client';
import { TablePagination } from '../../components/TablePagination';
import { FileText, Shield, Clock, Search } from 'lucide-react';

export const SuperadminLogs: React.FC = () => {
  const [logs, setLogs] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [searchQuery, setSearchQuery] = useState('');
  const [currentPage, setCurrentPage] = useState(1);
  const [pageSize, setPageSize] = useState(15);

  useEffect(() => {
    loadLogs();
  }, []);

  const loadLogs = async () => {
    try {
      setLoading(true);
      const res = await api.get('/api/platform/logs');
      setLogs(res || []);
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const handleSearchChange = (val: string) => {
    setSearchQuery(val);
    setCurrentPage(1);
  };

  const filteredLogs = logs.filter((log) => {
    const text = `${log.action} ${log.user_email || ''} ${log.tenant_name || ''} ${log.ip_address || ''}`.toLowerCase();
    return text.includes(searchQuery.toLowerCase());
  });

  const paginatedLogs = filteredLogs.slice(
    (currentPage - 1) * pageSize,
    currentPage * pageSize
  );

  return (
    <div className="space-y-6 max-w-7xl mx-auto">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <div className="flex items-center gap-2">
            <span className="p-1.5 rounded-lg bg-[#dcf1ff] text-[#1d5fd6]">
              <FileText className="w-5 h-5" />
            </span>
            <h1 className="text-xl sm:text-2xl font-bold text-[#131416]">Platform Audit Trail</h1>
          </div>
          <p className="text-xs text-[#5d5f60] mt-1">
            Immutable security and administrative action logs recorded across all tenants and superadmins.
          </p>
        </div>

        <div className="relative w-full sm:w-64">
          <Search className="w-4 h-4 text-[#5d5f60] absolute left-3 top-1/2 -translate-y-1/2" />
          <input
            type="text"
            placeholder="Search audit trail..."
            value={searchQuery}
            onChange={(e) => handleSearchChange(e.target.value)}
            className="w-full pl-9 pr-3.5 py-1.5 rounded-xl border border-[#e2e5e9] bg-white text-xs text-[#131416] focus:outline-hidden focus:border-[#1d5fd6] transition"
          />
        </div>
      </div>

      <div className="bg-white border border-[#e2e5e9] rounded-2xl overflow-hidden shadow-xs">
        {loading ? (
          <div className="p-6 divide-y divide-[#e2e5e9]">
            {[1, 2, 3, 4, 5].map((i) => (
              <div key={i} className="py-3 flex items-center justify-between gap-4">
                <div className="h-4 w-32 rounded-md skeleton-shimmer" />
                <div className="h-4 w-40 rounded-md skeleton-shimmer" />
                <div className="h-4 w-28 rounded-md skeleton-shimmer" />
                <div className="h-4 w-20 rounded-md skeleton-shimmer" />
                <div className="h-4 w-24 rounded-md skeleton-shimmer" />
              </div>
            ))}
          </div>
        ) : filteredLogs.length === 0 ? (
          <div className="p-12 text-center text-xs text-[#5d5f60]">No audit logs found.</div>
        ) : (
          <div>
            <div className="overflow-x-auto">
              <table className="w-full text-left border-collapse text-xs text-[#5d5f60]">
                <thead>
                  <tr className="border-b border-[#e2e5e9] bg-[#f8fafc] text-[11px] font-bold text-[#131416]">
                    <th className="py-3 px-4">Action</th>
                    <th className="py-3 px-4">Actor</th>
                    <th className="py-3 px-4">Workspace</th>
                    <th className="py-3 px-4">IP Address</th>
                    <th className="py-3 px-4 text-right">Timestamp</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-[#edf0f3]">
                  {paginatedLogs.map((log) => (
                    <tr key={log.id} className="hover:bg-[#f8fafc] transition">
                      <td className="py-3 px-4 font-bold text-[#131416]">{log.action}</td>
                      <td className="py-3 px-4">{log.user_email || log.user_name || 'System Actor'}</td>
                      <td className="py-3 px-4">
                        {log.tenant_name ? (
                          <span className="font-semibold text-[#0a3a80] px-2 py-0.5 rounded bg-[#dcf1ff] border border-[#b9e6fe]">
                            {log.tenant_name}
                          </span>
                        ) : (
                          <span className="text-[#5d5f60]">—</span>
                        )}
                      </td>
                      <td className="py-3 px-4 font-mono text-[11px] text-[#5d5f60]">{log.ip_address}</td>
                      <td className="py-3 px-4 text-right text-[11px] text-[#5d5f60]">
                        {new Date(log.created_at).toLocaleString()}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            <TablePagination
              currentPage={currentPage}
              totalItems={filteredLogs.length}
              pageSize={pageSize}
              onPageChange={setCurrentPage}
              onPageSizeChange={setPageSize}
              pageSizeOptions={[15, 25, 50, 100]}
              itemLabel="audit entries"
            />
          </div>
        )}
      </div>
    </div>
  );
};