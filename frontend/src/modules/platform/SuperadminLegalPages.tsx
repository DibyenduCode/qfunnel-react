import React, { useEffect, useState } from 'react';
import { api } from '../../api/client';
import { Scale, Plus, CheckCircle, AlertTriangle, ExternalLink, Trash2, Save, FileText, ArrowLeft, X } from 'lucide-react';

interface LegalPageItem {
  id: number;
  title: string;
  slug: string;
  meta_description: string;
  content: string;
  is_published: number;
  created_at: string;
  updated_at: string;
}

const CORE_SLUGS = ['privacy', 'terms', 'refund-policy', 'cookie-policy'];

export const SuperadminLegalPages: React.FC = () => {
  const [pages, setPages] = useState<LegalPageItem[]>([]);
  const [selectedPage, setSelectedPage] = useState<LegalPageItem | null>(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [msg, setMsg] = useState('');
  const [errorMsg, setErrorMsg] = useState('');

  // Form State
  const [title, setTitle] = useState('');
  const [slug, setSlug] = useState('');
  const [metaDescription, setMetaDescription] = useState('');
  const [content, setContent] = useState('');
  const [isPublished, setIsPublished] = useState(true);

  // New Page Modal
  const [createModalOpen, setCreateModalOpen] = useState(false);
  const [newTitle, setNewTitle] = useState('');
  const [newSlug, setNewSlug] = useState('');
  const [newMeta, setNewMeta] = useState('');
  const [newContent, setNewContent] = useState('');
  const [newPublished, setNewPublished] = useState(true);
  const [creating, setCreating] = useState(false);

  useEffect(() => {
    loadPages();
  }, []);

  const loadPages = async (preferredId?: number) => {
    try {
      setLoading(true);
      const res = await api.get('/api/platform/legal-pages');
      const loadedPages: LegalPageItem[] = res.pages || [];
      setPages(loadedPages);

      if (loadedPages.length > 0) {
        const found = preferredId
          ? loadedPages.find((p) => p.id === preferredId)
          : loadedPages[0];
        selectPage(found || loadedPages[0]);
      }
    } catch (err: any) {
      setErrorMsg(err.message || 'Failed to load legal pages');
    } finally {
      setLoading(false);
    }
  };

  const selectPage = (page: LegalPageItem) => {
    setSelectedPage(page);
    setTitle(page.title);
    setSlug(page.slug);
    setMetaDescription(page.meta_description || '');
    setContent(page.content || '');
    setIsPublished(Boolean(page.is_published));
    setMsg('');
    setErrorMsg('');
  };

  const handleSave = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedPage) return;
    setSaving(true);
    setMsg('');
    setErrorMsg('');
    try {
      await api.post(`/api/platform/legal-pages/${selectedPage.id}/update`, {
        title,
        slug,
        meta_description: metaDescription,
        content,
        is_published: isPublished ? 1 : 0,
      });
      setMsg(`Policy '${title}' successfully saved.`);
      await loadPages(selectedPage.id);
    } catch (err: any) {
      setErrorMsg(err.message || 'Failed to save changes');
    } finally {
      setSaving(false);
    }
  };

  const handleCreate = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!newTitle.trim()) return;
    setCreating(true);
    setErrorMsg('');
    try {
      const res = await api.post('/api/platform/legal-pages', {
        title: newTitle.trim(),
        slug: newSlug.trim(),
        meta_description: newMeta.trim(),
        content: newContent,
        is_published: newPublished ? 1 : 0,
      });
      setCreateModalOpen(false);
      setNewTitle('');
      setNewSlug('');
      setNewMeta('');
      setNewContent('');
      setNewPublished(true);
      await loadPages(res.id);
    } catch (err: any) {
      alert(err.message || 'Failed to create legal page');
    } finally {
      setCreating(false);
    }
  };

  const handleDelete = async (page: LegalPageItem) => {
    if (CORE_SLUGS.includes(page.slug)) {
      alert(`Cannot delete core system policy '${page.title}'. You may edit or unpublish it instead.`);
      return;
    }
    if (!window.confirm(`Permanently delete legal page '${page.title}'? This cannot be undone.`)) {
      return;
    }
    try {
      await api.post(`/api/platform/legal-pages/${page.id}/delete`, {});
      await loadPages();
    } catch (err: any) {
      alert(err.message || 'Failed to delete legal page');
    }
  };

  const getPublicUrl = (pageSlug: string) => {
    return CORE_SLUGS.includes(pageSlug) ? `/${pageSlug}` : `/legal/${pageSlug}`;
  };

  if (loading) {
    return (
      <div className="space-y-6 max-w-7xl mx-auto">
        <div className="h-10 w-64 rounded-xl skeleton-shimmer" />
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
          <div className="lg:col-span-4 h-96 rounded-2xl skeleton-shimmer" />
          <div className="lg:col-span-8 h-96 rounded-2xl skeleton-shimmer" />
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6 max-w-7xl mx-auto">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <div className="flex items-center gap-2">
            <span className="p-1.5 rounded-lg bg-[#fce7f3] text-[#9d174d]">
              <Scale className="w-5 h-5" />
            </span>
            <h1 className="text-xl sm:text-2xl font-bold text-[#131416]">Compliance & Legal CMS</h1>
          </div>
          <p className="text-xs text-[#5d5f60] mt-1">
            Manage public legal policies, terms of service, and compliance declarations dynamically.
          </p>
        </div>

        <div className="flex items-center gap-2">
          <button
            type="button"
            onClick={() => setCreateModalOpen(true)}
            className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-[#1d5fd6] text-white text-xs font-semibold hover:bg-[#154bb3] transition"
          >
            <Plus className="w-4 h-4" />
            <span>New Legal Page</span>
          </button>
        </div>
      </div>

      {msg && (
        <div className="p-3.5 rounded-xl bg-[#dcfae5] border border-[#bbf7d0] text-[#2f7a45] text-xs flex items-center gap-2">
          <CheckCircle className="w-4 h-4 shrink-0" />
          <span>{msg}</span>
        </div>
      )}

      {errorMsg && (
        <div className="p-3.5 rounded-xl bg-[#fef2f2] border border-[#fecaca] text-[#991b1b] text-xs flex items-center gap-2">
          <AlertTriangle className="w-4 h-4 shrink-0" />
          <span>{errorMsg}</span>
        </div>
      )}

      {/* Main 2-Column Work Area */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        {/* Left Column: Pages List (4 cols) */}
        <div className="lg:col-span-4 bg-white border border-[#e2e5e9] rounded-2xl p-4 space-y-3 shadow-xs">
          <div className="flex items-center justify-between px-1">
            <span className="text-xs font-bold text-[#131416]">Documents</span>
            <span className="text-[11px] font-semibold bg-[#f4f6f8] text-[#5d5f60] px-2 py-0.5 rounded-md">
              {pages.length} Pages
            </span>
          </div>

          <div className="space-y-1.5">
            {pages.map((p) => {
              const isSelected = selectedPage?.id === p.id;
              const isCore = CORE_SLUGS.includes(p.slug);
              return (
                <div
                  key={p.id}
                  onClick={() => selectPage(p)}
                  className={`p-3 rounded-xl border cursor-pointer transition ${
                    isSelected
                      ? 'border-[#1d5fd6] bg-[#f0f7ff]'
                      : 'border-[#e2e5e9] bg-white hover:bg-[#f8fafc]'
                  }`}
                >
                  <div className="flex items-start justify-between gap-2">
                    <div className="min-w-0 flex-1">
                      <div className="flex items-center gap-1.5">
                        <h3 className={`text-xs font-bold truncate ${isSelected ? 'text-[#1d5fd6]' : 'text-[#131416]'}`}>
                          {p.title}
                        </h3>
                        {isCore && (
                          <span className="px-1.5 py-0.2 rounded text-[9px] font-bold bg-[#f1f3f5] text-[#5d5f60]">
                            Core
                          </span>
                        )}
                      </div>
                      <div className="text-[11px] text-[#5d5f60] mt-0.5 font-mono">
                        /{p.slug}
                      </div>
                    </div>

                    <span
                      className={`px-2 py-0.5 rounded-full text-[10px] font-bold shrink-0 ${
                        p.is_published
                          ? 'bg-[#dcfae5] text-[#2f7a45] border border-[#bbf7d0]'
                          : 'bg-[#fef3c7] text-[#b45309] border border-[#fde68a]'
                      }`}
                    >
                      {p.is_published ? 'Published' : 'Draft'}
                    </span>
                  </div>

                  <div className="mt-2.5 pt-2 border-t border-[#edf0f3] flex items-center justify-between text-[11px] text-[#5d5f60]">
                    <span>{p.updated_at ? new Date(p.updated_at).toLocaleDateString() : '-'}</span>
                    <div className="flex items-center gap-2">
                      <a
                        href={getPublicUrl(p.slug)}
                        target="_blank"
                        rel="noreferrer"
                        onClick={(e) => e.stopPropagation()}
                        title="View Public Page"
                        className="text-[#5d5f60] hover:text-[#1d5fd6] transition"
                      >
                        <ExternalLink className="w-3.5 h-3.5" />
                      </a>
                      {!isCore && (
                        <button
                          type="button"
                          onClick={(e) => {
                            e.stopPropagation();
                            handleDelete(p);
                          }}
                          title="Delete Page"
                          className="text-[#5d5f60] hover:text-[#991b1b] transition"
                        >
                          <Trash2 className="w-3.5 h-3.5" />
                        </button>
                      )}
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
        </div>

        {/* Right Column: Active Page Editor (8 cols) */}
        <div className="lg:col-span-8 bg-white border border-[#e2e5e9] rounded-2xl p-6 shadow-xs space-y-6">
          {selectedPage ? (
            <form onSubmit={handleSave} className="space-y-4">
              <div className="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-[#edf0f3] gap-2">
                <div>
                  <h2 className="text-sm font-bold text-[#131416]">Edit Legal Policy</h2>
                  <p className="text-[11px] text-[#5d5f60]">Update content, title, and metadata for this policy.</p>
                </div>
                <div className="flex items-center gap-2">
                  <a
                    href={getPublicUrl(selectedPage.slug)}
                    target="_blank"
                    rel="noreferrer"
                    className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-[#e2e5e9] bg-white text-xs font-semibold text-[#131416] hover:bg-[#f8fafc] transition"
                  >
                    <ExternalLink className="w-3.5 h-3.5" />
                    <span>View Live</span>
                  </a>
                  <button
                    type="submit"
                    disabled={saving}
                    className="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-xl bg-[#1d5fd6] text-white text-xs font-semibold hover:bg-[#154bb3] transition disabled:opacity-60"
                  >
                    <Save className="w-3.5 h-3.5" />
                    <span>{saving ? 'Saving...' : 'Save Policy'}</span>
                  </button>
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label className="block text-xs font-bold text-[#131416] mb-1">Document Title *</label>
                  <input
                    type="text"
                    required
                    value={title}
                    onChange={(e) => setTitle(e.target.value)}
                    className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] text-xs text-[#131416] focus:outline-hidden focus:border-[#1d5fd6] transition"
                  />
                </div>

                <div>
                  <label className="block text-xs font-bold text-[#131416] mb-1">URL Slug *</label>
                  <input
                    type="text"
                    required
                    value={slug}
                    onChange={(e) => setSlug(e.target.value)}
                    className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] text-xs text-[#131416] focus:outline-hidden focus:border-[#1d5fd6] font-mono transition"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-bold text-[#131416] mb-1">Meta Description</label>
                <input
                  type="text"
                  value={metaDescription}
                  onChange={(e) => setMetaDescription(e.target.value)}
                  placeholder="Summary for search engines and social link previews..."
                  className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] text-xs text-[#131416] focus:outline-hidden focus:border-[#1d5fd6] transition"
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-[#131416] mb-1">Document Content (Markdown / HTML)</label>
                <textarea
                  rows={14}
                  value={content}
                  onChange={(e) => setContent(e.target.value)}
                  placeholder="Enter full legal page text..."
                  className="w-full p-3 rounded-xl border border-[#e2e5e9] text-xs text-[#131416] focus:outline-hidden focus:border-[#1d5fd6] font-mono leading-relaxed transition"
                />
              </div>

              <div className="flex items-center justify-between pt-2">
                <label className="flex items-center gap-2 cursor-pointer text-xs font-semibold text-[#131416]">
                  <input
                    type="checkbox"
                    checked={isPublished}
                    onChange={(e) => setIsPublished(e.target.checked)}
                    className="rounded border-[#e2e5e9] text-[#1d5fd6] focus:ring-0"
                  />
                  <span>Publish Document (Live to visitors and links)</span>
                </label>

                <button
                  type="submit"
                  disabled={saving}
                  className="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-[#1d5fd6] text-white text-xs font-semibold hover:bg-[#154bb3] transition disabled:opacity-60"
                >
                  <Save className="w-3.5 h-3.5" />
                  <span>{saving ? 'Saving...' : 'Save Policy'}</span>
                </button>
              </div>
            </form>
          ) : (
            <div className="p-12 text-center text-[#5d5f60] space-y-2">
              <FileText className="w-8 h-8 mx-auto opacity-50" />
              <p className="text-xs">Select a legal document from the left column to edit.</p>
            </div>
          )}
        </div>
      </div>

      {/* Create New Legal Page Modal */}
      {createModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs">
          <div className="bg-white border border-[#e2e5e9] rounded-2xl max-w-lg w-full p-6 space-y-4 shadow-xl">
            <div className="flex items-center justify-between pb-3 border-b border-[#edf0f3]">
              <h3 className="text-sm font-bold text-[#131416]">Create New Legal Document</h3>
              <button
                type="button"
                onClick={() => setCreateModalOpen(false)}
                className="text-[#5d5f60] hover:text-[#131416] p-1 rounded-lg hover:bg-[#f8fafc]"
                title="Close"
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            <form onSubmit={handleCreate} className="space-y-4">
              <div>
                <label className="block text-xs font-bold text-[#131416] mb-1">Page Title *</label>
                <input
                  type="text"
                  required
                  placeholder="e.g. Terms of Affiliate Agreement"
                  value={newTitle}
                  onChange={(e) => {
                    setNewTitle(e.target.value);
                    if (!newSlug) {
                      setNewSlug(e.target.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, ''));
                    }
                  }}
                  className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] text-xs text-[#131416] focus:outline-hidden focus:border-[#1d5fd6] transition"
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-[#131416] mb-1">URL Slug</label>
                <input
                  type="text"
                  placeholder="e.g. affiliate-terms"
                  value={newSlug}
                  onChange={(e) => setNewSlug(e.target.value)}
                  className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] text-xs text-[#131416] focus:outline-hidden focus:border-[#1d5fd6] font-mono transition"
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-[#131416] mb-1">Meta Description</label>
                <input
                  type="text"
                  placeholder="Brief summary..."
                  value={newMeta}
                  onChange={(e) => setNewMeta(e.target.value)}
                  className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] text-xs text-[#131416] focus:outline-hidden focus:border-[#1d5fd6] transition"
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-[#131416] mb-1">Initial Content</label>
                <textarea
                  rows={6}
                  placeholder="Write initial policy text..."
                  value={newContent}
                  onChange={(e) => setNewContent(e.target.value)}
                  className="w-full p-3 rounded-xl border border-[#e2e5e9] text-xs text-[#131416] focus:outline-hidden focus:border-[#1d5fd6] font-mono transition"
                />
              </div>

              <label className="flex items-center gap-2 cursor-pointer text-xs font-semibold text-[#131416]">
                <input
                  type="checkbox"
                  checked={newPublished}
                  onChange={(e) => setNewPublished(e.target.checked)}
                  className="rounded border-[#e2e5e9] text-[#1d5fd6] focus:ring-0"
                />
                <span>Publish Immediately</span>
              </label>

              <div className="flex items-center justify-end gap-2 pt-3 border-t border-[#edf0f3]">
                <button
                  type="button"
                  onClick={() => setCreateModalOpen(false)}
                  className="px-3 py-1.5 rounded-xl border border-[#e2e5e9] text-xs font-semibold text-[#5d5f60] hover:bg-[#f8fafc] transition"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={creating}
                  className="px-4 py-1.5 rounded-xl bg-[#1d5fd6] text-white text-xs font-semibold hover:bg-[#154bb3] transition disabled:opacity-60"
                >
                  {creating ? 'Creating...' : 'Create Legal Page'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
