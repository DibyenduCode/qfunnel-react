import React, { useState } from 'react';
import {
  HelpCircle,
  Sliders,
  FileText,
  Video,
  Image as ImageIcon,
  Flag,
  Calendar,
  Sparkles,
  GitBranch,
  Play,
  Search,
  ChevronLeft,
  Plus,
  Layers,
  GripVertical
} from 'lucide-react';

export interface NodeDefinition {
  type: string;
  subType?: string;
  title: string;
  description: string;
  category: 'input' | 'media' | 'logic' | 'outcome';
  icon: React.ComponentType<{ className?: string }>;
  color: string;
  bgColor: string;
  borderColor: string;
}

export const AVAILABLE_NODES: NodeDefinition[] = [
  // 1. Inputs & Questions
  {
    type: 'question',
    subType: 'choice',
    title: 'Multiple Choice',
    description: 'Branching question with custom choice options',
    category: 'input',
    icon: HelpCircle,
    color: 'text-[#1d5fd6]',
    bgColor: 'bg-[#dcf1ff]',
    borderColor: 'border-[#b9e6fe]',
  },
  {
    type: 'question',
    subType: 'slider',
    title: 'Numeric Slider',
    description: 'Range slider for budgets, ratings and calculator math',
    category: 'input',
    icon: Sliders,
    color: 'text-[#0f766e]',
    bgColor: 'bg-[#ccfbf1]',
    borderColor: 'border-[#99f6e4]',
  },
  {
    type: 'form',
    title: 'Lead Capture Form',
    description: 'Collect name, email, phone and custom lead fields',
    category: 'input',
    icon: FileText,
    color: 'text-[#3730a3]',
    bgColor: 'bg-[#e0e7ff]',
    borderColor: 'border-[#c7d2fe]',
  },

  // 2. Media & Interstitials
  {
    type: 'content',
    title: 'Content Slide',
    description: 'Image banner, story, or infographic with next button',
    category: 'media',
    icon: ImageIcon,
    color: 'text-[#1d5fd6]',
    bgColor: 'bg-[#dcf1ff]',
    borderColor: 'border-[#b9e6fe]',
  },
  {
    type: 'video_gate',
    title: 'Interactive Video',
    description: 'Standard presentation video or timed lead capture gate',
    category: 'media',
    icon: Video,
    color: 'text-[#c41c29]',
    bgColor: 'bg-[#ffedea]',
    borderColor: 'border-[#fad2cd]',
  },
  {
    type: 'loader',
    title: 'Calculating Loader',
    description: 'Progress interstitial that auto-advances',
    category: 'media',
    icon: Sparkles,
    color: 'text-[#b45309]',
    bgColor: 'bg-[#fef3c7]',
    borderColor: 'border-[#fde68a]',
  },

  // 3. Logic & Rules
  {
    type: 'start',
    title: 'Start Step',
    description: 'Initial entry point where visitors enter your funnel',
    category: 'logic',
    icon: Play,
    color: 'text-[#2f7a45]',
    bgColor: 'bg-[#dcfae5]',
    borderColor: 'border-[#bbf7d0]',
  },
  {
    type: 'condition',
    title: 'Conditional Router',
    description: 'Route visitors based on score thresholds or rules',
    category: 'logic',
    icon: GitBranch,
    color: 'text-[#c2410c]',
    bgColor: 'bg-[#ffedd5]',
    borderColor: 'border-[#fed7aa]',
  },

  // 4. Outcomes & Conversions
  {
    type: 'booking',
    title: 'Appointment Booking',
    description: 'Embed Calendly or Cal.com schedule link',
    category: 'outcome',
    icon: Calendar,
    color: 'text-[#2f7a45]',
    bgColor: 'bg-[#dcfae5]',
    borderColor: 'border-[#bbf7d0]',
  },
  {
    type: 'destination',
    title: 'Outcome & Quote',
    description: 'Final destination, thank-you page, or direct call link',
    category: 'outcome',
    icon: Flag,
    color: 'text-[#1d5fd6]',
    bgColor: 'bg-[#dcf1ff]',
    borderColor: 'border-[#b9e6fe]',
  },
];

interface NodePaletteProps {
  isOpen: boolean;
  onToggle: () => void;
  onAddNode: (type: string, subType?: string, position?: { x: number; y: number }) => void;
}

export const NodePalette: React.FC<NodePaletteProps> = ({
  isOpen,
  onToggle,
  onAddNode,
}) => {
  const [search, setSearch] = useState('');
  const [selectedCategory, setSelectedCategory] = useState<string>('all');

  const filteredNodes = AVAILABLE_NODES.filter((n) => {
    const matchesCategory =
      selectedCategory === 'all' || n.category === selectedCategory;
    const matchesSearch =
      n.title.toLowerCase().includes(search.toLowerCase()) ||
      n.description.toLowerCase().includes(search.toLowerCase());
    return matchesCategory && matchesSearch;
  });

  const handleDragStart = (e: React.DragEvent, nodeDef: NodeDefinition) => {
    e.dataTransfer.setData('application/reactflow-type', nodeDef.type);
    if (nodeDef.subType) {
      e.dataTransfer.setData('application/reactflow-subtype', nodeDef.subType);
    }
    e.dataTransfer.effectAllowed = 'move';
  };

  return (
    <aside
      className={`relative z-20 flex bg-white border border-[#e8eaec] rounded-[16px] overflow-hidden shrink-0 transition-[width] duration-150 ${
        isOpen ? 'w-[232px]' : 'w-12'
      }`}
    >
      {/* Collapsed Rail Button */}
      {!isOpen ? (
        <div className="w-12 flex flex-col items-center py-3 gap-3">
          <button
            type="button"
            onClick={onToggle}
            className="w-8 h-8 rounded-[6px] bg-[#f4f6f8] text-[#131416] hover:bg-[#ebedee] transition-colors flex items-center justify-center border border-[#e8eaec]"
            title="Expand Node Library"
          >
            <Plus className="w-4 h-4" />
          </button>
          <div className="h-4 w-[1px] bg-[#e8eaec]" />
          <div
            className="text-[11px] font-medium text-[#5d5f60] rotate-90 whitespace-nowrap select-none mt-6 cursor-pointer"
            onClick={onToggle}
          >
            Library
          </div>
        </div>
      ) : (
        <div className="w-[232px] flex flex-col h-full overflow-hidden">
          {/* Header */}
          <div className="p-3 border-b border-[#f0f1f2] flex items-center justify-between bg-white">
            <div className="flex items-center gap-2">
              <div className="w-6 h-6 rounded-[6px] bg-[#ebedee] text-[#131416] flex items-center justify-center">
                <Layers className="w-3.5 h-3.5" />
              </div>
              <div>
                <h3 className="text-xs font-semibold text-[#131416]">Node Library</h3>
                <p className="text-[11px] text-[#5d5f60]">Click or drag to canvas</p>
              </div>
            </div>
            <button
              type="button"
              onClick={onToggle}
              className="p-1 rounded-[6px] text-[#5d5f60] hover:text-[#131416] hover:bg-[#f4f6f8] transition-colors"
              title="Collapse Library"
            >
              <ChevronLeft className="w-4 h-4" />
            </button>
          </div>

          {/* Search Box & Category Filters */}
          <div className="p-2.5 border-b border-[#f0f1f2]">
            <div className="relative">
              <Search className="w-3.5 h-3.5 text-[#5d5f60] absolute left-2.5 top-2.5 pointer-events-none" />
              <input
                type="text"
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                placeholder="Search steps..."
                className="w-full pl-8 pr-2.5 py-1.5 rounded-[6px] border border-[#e8eaec] text-xs text-[#131416] placeholder-[#5d5f60] focus:outline-none focus:border-[#1d5fd6]"
              />
            </div>

            {/* Category Pills */}
            <div className="flex items-center gap-1 mt-2 overflow-x-auto no-scrollbar">
              {[
                { id: 'all', label: 'All' },
                { id: 'input', label: 'Inputs' },
                { id: 'media', label: 'Media' },
                { id: 'logic', label: 'Logic' },
                { id: 'outcome', label: 'Outcomes' },
              ].map((cat) => (
                <button
                  key={cat.id}
                  type="button"
                  onClick={() => setSelectedCategory(cat.id)}
                  className={`px-2 py-0.5 rounded-[4px] text-[10px] font-medium transition-colors whitespace-nowrap ${
                    selectedCategory === cat.id
                      ? 'bg-[#1d5fd6] text-white shadow-xs'
                      : 'bg-[#f4f6f8] text-[#5d5f60] hover:bg-[#dcf1ff] hover:text-[#0a3a80]'
                  }`}
                >
                  {cat.label}
                </button>
              ))}
            </div>
          </div>

          {/* Draggable Node Cards List */}
          <div className="flex-1 overflow-y-auto p-2.5 space-y-1.5">
            {filteredNodes.length === 0 ? (
              <div className="p-6 text-center text-xs text-[#5d5f60]">
                No steps found matching "{search}".
              </div>
            ) : (
              filteredNodes.map((nodeDef, idx) => {
                const IconComponent = nodeDef.icon;
                return (
                  <div
                    key={idx}
                    draggable
                    onDragStart={(e) => handleDragStart(e, nodeDef)}
                    onClick={() => onAddNode(nodeDef.type, nodeDef.subType)}
                    className="group h-[44px] px-2 rounded-xl border border-[#e2e5e9] bg-white hover:border-[#1d5fd6] hover:bg-[#dcf1ff]/20 transition cursor-grab active:cursor-grabbing flex items-center gap-2 select-none"
                    title="Click to add or drag onto canvas"
                  >
                    <div
                      className={`w-7 h-7 rounded-lg ${nodeDef.bgColor} ${nodeDef.color} flex items-center justify-center shrink-0 border ${nodeDef.borderColor}`}
                    >
                      <IconComponent className="w-3.5 h-3.5" />
                    </div>

                    <div className="min-w-0 flex-1">
                      <div className="flex items-center justify-between gap-1">
                        <span className="text-xs font-semibold text-[#131416] group-hover:text-[#1d5fd6] truncate transition">
                          {nodeDef.title}
                        </span>
                        <GripVertical className="w-3 h-3 text-[#cdced1] group-hover:text-[#5d5f60] shrink-0" />
                      </div>
                    </div>
                  </div>
                );
              })
            )}
          </div>

          {/* Footnote */}
          <div className="p-2.5 bg-[#f4f6f8] border-t border-[#f0f1f2] text-[11px] text-[#5d5f60]">
            Drag wire from node handle for quick-add.
          </div>
        </div>
      )}
    </aside>
  );
};
