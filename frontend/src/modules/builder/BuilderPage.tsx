import React, { useState, useEffect, useCallback, useMemo } from 'react';
import { useParams } from 'react-router-dom';
import {
  ReactFlow,
  MiniMap,
  Controls,
  useNodesState,
  useEdgesState,
  addEdge,
  Connection,
  Edge,
  Node,
  ReactFlowProvider,
  useReactFlow,
  FinalConnectionState,
} from '@xyflow/react';
import '@xyflow/react/dist/style.css';

import { api } from '../../api/client';
import { StartNode } from './nodes/StartNode';
import { FormNode } from './nodes/FormNode';
import { QuestionNode } from './nodes/QuestionNode';
import { VideoGateNode } from './nodes/VideoGateNode';
import { DestinationNode } from './nodes/DestinationNode';
import { LoaderNode } from './nodes/LoaderNode';
import { BookingNode } from './nodes/BookingNode';
import { NodeInspector } from './inspector/NodeInspector';
import { BuilderToolbar } from './toolbar/BuilderToolbar';
import { NodePalette, AVAILABLE_NODES } from './palette/NodePalette';
import {
  X,
  ArrowRight,
  Plus,
  Play
} from 'lucide-react';

interface QuickAddState {
  fromNode: Node;
  fromHandleId: string | null;
  screenPos: { x: number; y: number };
  flowPos: { x: number; y: number };
}

const BuilderInner: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const { screenToFlowPosition } = useReactFlow();

  const [funnel, setFunnel] = useState<any>(null);
  const [nodes, setNodes, onNodesChange] = useNodesState<Node>([]);
  const [edges, setEdges, onEdgesChange] = useEdgesState<Edge>([]);
  const [selectedNodeId, setSelectedNodeId] = useState<string | null>(null);

  const [isPaletteOpen, setIsPaletteOpen] = useState(true);
  const [quickAdd, setQuickAdd] = useState<QuickAddState | null>(null);

  const [loading, setLoading] = useState(true);
  const [isDirty, setIsDirty] = useState(false);
  const [isSaving, setIsSaving] = useState(false);
  const [isPublishing, setIsPublishing] = useState(false);
  const [validationErrors, setValidationErrors] = useState<string[]>([]);
  const [bannerMsg, setBannerMsg] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

  // Define node types memoized for React Flow performance
  const nodeTypes = useMemo(
    () => ({
      start: StartNode,
      form: FormNode,
      question: QuestionNode,
      video_gate: VideoGateNode,
      destination: DestinationNode,
      loader: LoaderNode,
      booking: BookingNode,
    }),
    []
  );

  useEffect(() => {
    loadGraph();
  }, [id]);

  // Global Keyboard Shortcuts (Ctrl+S, L for layout, Escape)
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      const activeEl = document.activeElement;
      const isInput =
        activeEl &&
        (activeEl.tagName === 'INPUT' ||
          activeEl.tagName === 'TEXTAREA' ||
          activeEl.tagName === 'SELECT' ||
          (activeEl as HTMLElement).isContentEditable);

      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
        e.preventDefault();
        handleSaveGraph();
        return;
      }

      if (e.key === 'Escape') {
        setSelectedNodeId(null);
        setQuickAdd(null);
        return;
      }

      if (!isInput && e.key.toLowerCase() === 'l' && !e.ctrlKey && !e.metaKey && !e.altKey) {
        e.preventDefault();
        handleAutoArrange();
        return;
      }
    };

    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [nodes, edges, isDirty]);

  const loadGraph = async () => {
    try {
      setLoading(true);
      const res = await api.get(`/api/funnels/${id}/graph`);
      setFunnel(res.funnel);

      // Convert backend nodes to React Flow format
      const rfNodes: Node[] = (res.nodes || []).map((n: any) => {
        let parsedConfig: any = {};
        if (n.config_json) {
          parsedConfig = typeof n.config_json === 'string' ? JSON.parse(n.config_json) : n.config_json;
        }

        return {
          id: String(n.id),
          type: n.node_type,
          position: {
            x: Number(n.position_x) || 100,
            y: Number(n.position_y) || 100,
          },
          data: {
            ...n,
            title: n.title,
            variable_key: n.variable_key || 'Q1',
            input_type: n.input_type || n.question?.question_type || 'single_choice',
            slider_config: n.slider_config,
            question: n.question,
            options: n.question?.options || n.options || [],
            form: n.form,
            fields: n.form?.fields || n.fields || [],
            destination: n.destination || {},
            destination_mode: n.destination_mode,
            calculator: n.calculator,
            quick_connect: n.quick_connect,
            video_gate: n.video_gate,
            loader: parsedConfig.loader || n.loader,
            booking: parsedConfig.booking || n.booking,
            config: parsedConfig || n.config || {},
          },
        };
      });

      // Convert backend edges to React Flow format
      const rfEdges: Edge[] = (res.edges || []).map((e: any, idx: number) => ({
        id: e.id ? String(e.id) : `e-${e.source_node_id}-${e.target_node_id}-${idx}`,
        source: String(e.source_node_id),
        sourceHandle: e.source_option_id ? String(e.source_option_id) : 'output',
        target: String(e.target_node_id),
        targetHandle: 'input',
        animated: true,
        style: { stroke: '#1b6c80', strokeWidth: 1.5 },
      }));

      setNodes(rfNodes);
      setEdges(rfEdges);
      setIsDirty(false);
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const onConnect = useCallback(
    (params: Connection) => {
      setEdges((eds) =>
        addEdge(
          {
            ...params,
            animated: true,
            style: { stroke: '#1b6c80', strokeWidth: 1.5 },
          },
          eds
        )
      );
      setIsDirty(true);
    },
    [setEdges]
  );

  // Contextual Wire Drag-and-Release Handler
  const onConnectEnd = useCallback(
    (event: MouseEvent | TouchEvent, connectionState: FinalConnectionState) => {
      if (!connectionState.isValid && connectionState.fromNode) {
        const clientX = 'clientX' in event ? event.clientX : event.touches[0].clientX;
        const clientY = 'clientY' in event ? event.clientY : event.touches[0].clientY;

        const flowPos = screenToFlowPosition({ x: clientX, y: clientY });

        setQuickAdd({
          fromNode: connectionState.fromNode,
          fromHandleId: connectionState.fromHandle?.id || null,
          screenPos: { x: clientX, y: clientY },
          flowPos,
        });
      }
    },
    [screenToFlowPosition]
  );

  const onNodeClick = useCallback((_: any, node: Node) => {
    setSelectedNodeId(node.id);
    setQuickAdd(null);
  }, []);

  const onPaneClick = useCallback(() => {
    setSelectedNodeId(null);
    setQuickAdd(null);
  }, []);

  const handleUpdateNode = useCallback(
    (updatedNode: Node) => {
      setNodes((nds) =>
        nds.map((n) => (n.id === updatedNode.id ? updatedNode : n))
      );
      setIsDirty(true);
    },
    [setNodes]
  );

  const handleDeleteNode = useCallback(
    (nodeId: string) => {
      setNodes((nds) => nds.filter((n) => n.id !== nodeId));
      setEdges((eds) => eds.filter((e) => e.source !== nodeId && e.target !== nodeId));
      setSelectedNodeId(null);
      setIsDirty(true);
    },
    [setNodes, setEdges]
  );

  const handleModeChange = async (mode: 'quiz' | 'calculator') => {
    try {
      await api.post(`/api/funnels/${id}/mode`, { funnel_mode: mode });
      setFunnel((prev: any) => ({ ...prev, funnel_mode: mode }));
      setBannerMsg({
        type: 'success',
        text: `Switched to ${mode === 'calculator' ? 'Calculator & Quote' : 'Quiz'} Mode.`,
      });
      setTimeout(() => setBannerMsg(null), 3000);
    } catch (err: any) {
      setBannerMsg({ type: 'error', text: err.message || 'Failed to update funnel mode.' });
    }
  };

  const handleAddNode = useCallback(
    (type: string, subType?: string, position?: { x: number; y: number }): string => {
      const newId = `node_${Date.now()}`;
      const x = position ? position.x : Math.floor(Math.random() * 150) + 200;
      const y = position ? position.y : Math.floor(Math.random() * 150) + 150;

      const currentQuestions = nodes.filter((n) => n.type === 'question');
      const qNum = currentQuestions.length + 1;

      let initialData: any = {
        id: newId,
        node_key: newId,
        node_type: type,
        title: `New ${type.replace('_', ' ')}`,
        config: {},
      };

      if (type === 'start') {
        initialData.title = 'Start';
        initialData.is_start = 1;
        initialData.config = {
          subtitle: 'Public entry point. Connects visitors to the first step.',
        };
      } else if (type === 'question') {
        if (subType === 'slider') {
          initialData.title = 'Select your budget';
          initialData.input_type = 'slider';
          initialData.variable_key = `Q${qNum}`;
          initialData.slider_config = {
            min_value: 10,
            max_value: 1000,
            step_value: 10,
            default_value: 100,
            unit_label: 'INR',
            unit_prefix: '₹',
          };
          initialData.question = {
            question_text: 'Select your budget range',
            question_type: 'slider',
            options: [],
          };
          initialData.options = [];
        } else {
          initialData.title = 'What is your primary goal?';
          initialData.input_type = 'single_choice';
          initialData.variable_key = `Q${qNum}`;
          initialData.question = {
            question_text: 'What is your primary goal?',
            question_type: 'single_choice',
            options: [
              { id: Date.now() + 1, option_key: 'opt_1', label: 'Option A', value: 10 },
              { id: Date.now() + 2, option_key: 'opt_2', label: 'Option B', value: 20 },
            ],
          };
          initialData.options = initialData.question.options;
        }
      } else if (type === 'form') {
        initialData.title = 'Lead Capture Form';
        initialData.form = {
          heading: 'Get Your Custom Plan',
          subheading: 'Please enter your contact details to receive your customized report.',
          button_text: 'Submit & Get Results',
          submit_action: 'redirect',
          success_heading: 'Thank You!',
          success_message: 'Your details have been submitted successfully.',
          fields: [
            { id: Date.now() + 1, label: 'Full Name', field_key: 'name', field_type: 'text', placeholder: 'Dr. Sarah Chen', is_required: 1 },
            { id: Date.now() + 2, label: 'Email Address', field_key: 'email', field_type: 'email', placeholder: 'sarah.chen@apexhealth.org', is_required: 1 },
            { id: Date.now() + 3, label: 'Phone Number', field_key: 'phone', field_type: 'tel', placeholder: '+1 (555) 234-5678', is_required: 1 },
          ],
        };
        initialData.fields = initialData.form.fields;
      } else if (type === 'video_gate') {
        initialData.title = 'Watch Video Presentation';
        initialData.video_gate = {
          video_url: 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
          gate_enabled: true,
          gate_timestamp_seconds: 45,
          gate_headline: 'Watch presentation to continue',
          allow_skip_gate: false,
          on_video_end: 'auto_advance',
        };
        initialData.config = initialData.video_gate;
      } else if (type === 'loader') {
        initialData.title = 'Analyzing Responses...';
        initialData.loader = {
          headline: 'Analyzing your diagnostic responses...',
          subtext: 'Matching the optimal care pathway based on your answers...',
          duration_seconds: 2.5,
        };
        initialData.config = initialData.loader;
      } else if (type === 'booking') {
        initialData.title = 'Schedule Consultation';
        initialData.booking = {
          headline: 'Schedule Your 1-on-1 Consultation',
          provider: 'calendly',
          booking_url: 'https://calendly.com/practice/30min',
          embed_mode: 'inline',
          prefill_contact: true,
        };
        initialData.config = initialData.booking;
      } else if (type === 'destination') {
        initialData.title = 'Thank You Outcome';
        initialData.destination_mode = 'redirect';
        initialData.destination = {
          name: 'Thank You Outcome',
          url: '',
        };
        initialData.calculator = {
          enabled: funnel?.funnel_mode === 'calculator',
          formula_expression: '@Q1 * 100',
          result_label: 'Your Estimated Quote',
          result_prefix: '₹',
          result_suffix: '',
          decimal_places: 0,
        };
        initialData.quick_connect = {
          enabled: false,
          action_type: 'both',
          phone_number: '',
          call_button_text: 'Call Specialist Now',
          whatsapp_number: '',
          whatsapp_button_text: 'Chat on WhatsApp',
          whatsapp_message: 'Hi! I just completed the {{funnel_name}} and received my quote: {{formatted_quote}}.',
        };
      }

      const newNode: Node = {
        id: newId,
        type,
        position: { x, y },
        data: initialData,
      };

      setNodes((nds) => [...nds, newNode]);
      setSelectedNodeId(newId);
      setIsDirty(true);
      return newId;
    },
    [nodes, funnel, setNodes]
  );

  // Quick-Add Next Step Selection Callback
  const handleQuickAddSelect = (type: string, subType?: string) => {
    if (!quickAdd) return;

    const newTargetId = handleAddNode(type, subType, quickAdd.flowPos);

    // Auto-create edge from source to new target
    const newEdge: Edge = {
      id: `e-${quickAdd.fromNode.id}-${newTargetId}-${Date.now()}`,
      source: quickAdd.fromNode.id,
      sourceHandle: quickAdd.fromHandleId || undefined,
      target: newTargetId,
      targetHandle: 'input',
      animated: true,
      style: { stroke: '#1d5fd6', strokeWidth: 1.5 },
    };

    setEdges((eds) => addEdge(newEdge, eds));
    setQuickAdd(null);
    setIsDirty(true);
  };

  // Drag over and Drop from Left Palette
  const onDragOver = useCallback((event: React.DragEvent) => {
    event.preventDefault();
    event.dataTransfer.dropEffect = 'move';
  }, []);

  const onDrop = useCallback(
    (event: React.DragEvent) => {
      event.preventDefault();
      const type = event.dataTransfer.getData('application/reactflow-type');
      const subType = event.dataTransfer.getData('application/reactflow-subtype');
      if (!type) return;

      const position = screenToFlowPosition({
        x: event.clientX,
        y: event.clientY,
      });

      handleAddNode(type, subType, position);
    },
    [screenToFlowPosition, handleAddNode]
  );

  const handleAutoArrange = () => {
    if (nodes.length === 0) return;

    const adj = new Map<string, string[]>();
    const inDegree = new Map<string, number>();

    nodes.forEach((n) => {
      adj.set(n.id, []);
      inDegree.set(n.id, 0);
    });

    edges.forEach((e) => {
      if (adj.has(e.source) && inDegree.has(e.target)) {
        adj.get(e.source)!.push(e.target);
        inDegree.set(e.target, (inDegree.get(e.target) || 0) + 1);
      }
    });

    const startNode =
      nodes.find((n) => n.type === 'start') ||
      nodes.find((n) => (inDegree.get(n.id) || 0) === 0) ||
      nodes[0];

    const queue: { id: string; layer: number }[] = [{ id: startNode.id, layer: 0 }];
    const visited = new Set<string>([startNode.id]);
    const nodeLayers = new Map<string, number>([[startNode.id, 0]]);

    while (queue.length > 0) {
      const { id: currentId, layer } = queue.shift()!;
      const neighbors = adj.get(currentId) || [];
      for (const neighbor of neighbors) {
        if (!visited.has(neighbor)) {
          visited.add(neighbor);
          nodeLayers.set(neighbor, layer + 1);
          queue.push({ id: neighbor, layer: layer + 1 });
        }
      }
    }

    let maxLayer = Math.max(0, ...Array.from(nodeLayers.values()));
    nodes.forEach((n) => {
      if (!nodeLayers.has(n.id)) {
        maxLayer += 1;
        nodeLayers.set(n.id, maxLayer);
      }
    });

    const layerGroups = new Map<number, Node[]>();
    nodes.forEach((n) => {
      const layer = nodeLayers.get(n.id) || 0;
      if (!layerGroups.has(layer)) layerGroups.set(layer, []);
      layerGroups.get(layer)!.push(n);
    });

    const columnWidth = 360;
    const rowHeight = 240;
    const startX = 80;
    const startY = 80;

    const arrangedNodes = nodes.map((n) => {
      const layer = nodeLayers.get(n.id) || 0;
      const group = layerGroups.get(layer) || [n];
      const indexInGroup = group.findIndex((gn) => gn.id === n.id);
      const totalInGroup = group.length;

      const yOffset = (indexInGroup - (totalInGroup - 1) / 2) * rowHeight;

      return {
        ...n,
        position: {
          x: startX + layer * columnWidth,
          y: Math.max(40, startY + yOffset + 140),
        },
      };
    });

    setNodes(arrangedNodes);
    setIsDirty(true);
    setBannerMsg({ type: 'success', text: 'Flow automatically arranged into clean hierarchy.' });
    setTimeout(() => setBannerMsg(null), 2500);
  };

  const handleSaveGraph = async () => {
    try {
      setIsSaving(true);
      setBannerMsg(null);

      const payload = {
        nodes: nodes.map((n) => {
          const d = n.data as any;
          return {
            id: n.id,
            node_key: d.node_key || n.id,
            node_type: n.type,
            title: d.title || 'Step',
            is_start: d.is_start || n.type === 'start',
            position_x: n.position.x,
            position_y: n.position.y,
            variable_key: d.variable_key,
            input_type: d.input_type || d.question?.question_type,
            slider_config: d.slider_config,
            question: d.question,
            options: d.options || d.question?.options,
            destination_mode: d.destination_mode,
            destination: d.destination,
            calculator: d.calculator,
            quick_connect: d.quick_connect,
            form: d.form,
            fields: d.fields || d.form?.fields,
            video_gate: d.video_gate || d.config,
            loader: d.loader || d.config?.loader,
            booking: d.booking || d.config?.booking,
            config: d.config,
          };
        }),
        edges: edges.map((e) => ({
          source_node_id: e.source,
          source_option_id: e.sourceHandle && e.sourceHandle !== 'output' ? e.sourceHandle : null,
          target_node_id: e.target,
        })),
      };

      await api.post(`/api/funnels/${id}/graph`, payload);
      setIsDirty(false);
      setBannerMsg({ type: 'success', text: 'Canvas saved successfully.' });
      setTimeout(() => setBannerMsg(null), 3000);
    } catch (err: any) {
      setBannerMsg({ type: 'error', text: err.message || 'Failed to save graph.' });
    } finally {
      setIsSaving(false);
    }
  };

  const handlePublish = async () => {
    try {
      setIsPublishing(true);
      setValidationErrors([]);
      setBannerMsg(null);

      if (isDirty) {
        await handleSaveGraph();
      }

      const res = await api.post(`/api/funnels/${id}/publish`);
      setFunnel((prev: any) => ({ ...prev, status: 'published' }));
      setBannerMsg({
        type: 'success',
        text: `Published successfully as Version ${res.version_number || 1}.`,
      });
      setTimeout(() => setBannerMsg(null), 4000);
    } catch (err: any) {
      if (err.data && Array.isArray(err.data)) {
        setValidationErrors(err.data.map((item: any) => item.message || String(item)));
      } else {
        setValidationErrors([err.message || 'Validation failed. Ensure all paths have next steps.']);
      }
    } finally {
      setIsPublishing(false);
    }
  };

  const selectedNode = nodes.find((n) => n.id === selectedNodeId);

  if (loading) {
    return (
      <div className="flex-1 h-screen flex items-center justify-center bg-[#f9fbfc]">
        <div className="flex flex-col items-center gap-3">
          <div className="w-7 h-7 border-2 border-[#1b6c80] border-t-transparent rounded-full animate-spin" />
          <span className="text-xs font-medium text-[#5d5f60]">Loading funnel canvas...</span>
        </div>
      </div>
    );
  }

  return (
    <div className="flex flex-col h-screen w-screen overflow-hidden bg-[#f9fbfc]">
      {/* 44px Top Toolbar */}
      <BuilderToolbar
        funnel={funnel}
        isDirty={isDirty}
        isSaving={isSaving}
        isPublishing={isPublishing}
        funnelMode={funnel?.funnel_mode || 'quiz'}
        onModeChange={handleModeChange}
        onSave={handleSaveGraph}
        onPublish={handlePublish}
        onAddNode={handleAddNode}
        onAutoArrange={handleAutoArrange}
        isPaletteOpen={isPaletteOpen}
        onTogglePalette={() => setIsPaletteOpen((prev) => !prev)}
      />

      {/* Global Toast Notification */}
      {bannerMsg && (
        <div
          className={`px-4 py-2 text-xs font-medium text-center border-b ${
            bannerMsg.type === 'success'
              ? 'bg-[#dcfae5] text-[#04250e] border-[#bbf0cc]'
              : 'bg-[#ffedea] text-[#831c1e] border-[#fad2cd]'
          }`}
        >
          {bannerMsg.text}
        </div>
      )}

      {/* Validation Errors Overlay */}
      {validationErrors.length > 0 && (
        <div className="bg-[#ffedea] border-b border-[#fad2cd] p-3 px-6 flex items-start justify-between text-xs text-[#831c1e]">
          <div>
            <span className="font-semibold block mb-1">
              Publishing blocked due to incomplete connections:
            </span>
            <ul className="list-disc pl-4 space-y-0.5 text-[11px]">
              {validationErrors.map((err, i) => (
                <li key={i}>{err}</li>
              ))}
            </ul>
          </div>
          <button
            onClick={() => setValidationErrors([])}
            className="text-xs font-semibold text-[#831c1e] underline"
          >
            Dismiss
          </button>
        </div>
      )}

      {/* Main Workspace Layout (Left Palette + Center Canvas + Right Inspector) */}
      <div className="flex-1 flex overflow-hidden p-2 gap-3 relative">
        {/* 1. Left Collapsible Node Library Palette */}
        <NodePalette
          isOpen={isPaletteOpen}
          onToggle={() => setIsPaletteOpen((prev) => !prev)}
          onAddNode={(type, subType) => handleAddNode(type, subType)}
        />

        {/* 2. Center React Flow Canvas (Flat well #f4f6f8) */}
        <div
          className="flex-1 h-full relative bg-[#f4f6f8] rounded-[16px] border border-[#e8eaec] overflow-hidden"
          onDragOver={onDragOver}
          onDrop={onDrop}
        >
          <ReactFlow
            nodes={nodes}
            edges={edges}
            onNodesChange={(changes) => {
              onNodesChange(changes);
              setIsDirty(true);
            }}
            onEdgesChange={(changes) => {
              onEdgesChange(changes);
              setIsDirty(true);
            }}
            onConnect={onConnect}
            onConnectEnd={onConnectEnd}
            onNodeClick={onNodeClick}
            onPaneClick={onPaneClick}
            nodeTypes={nodeTypes}
            fitView
            proOptions={{ hideAttribution: true }}
          >
            <Controls className="!bg-white !border !border-[#e8eaec] !rounded-[8px] !shadow-none" />
            <MiniMap
              className="!bg-white !border !border-[#e8eaec] !rounded-[8px] !shadow-none overflow-hidden"
              nodeColor={(n) => {
                if (n.type === 'start') return '#2f7a45';
                if (n.type === 'form') return '#0369a1';
                if (n.type === 'question') {
                  return (n.data as any)?.input_type === 'slider' ? '#0f766e' : '#1d5fd6';
                }
                if (n.type === 'video_gate') return '#c41c29';
                if (n.type === 'loader') return '#b45309';
                if (n.type === 'booking') return '#2f7a45';
                if (n.type === 'destination') return '#1d5fd6';
                return '#5d5f60';
              }}
            />
          </ReactFlow>

          {/* Empty Canvas Callout */}
          {nodes.length === 0 && (
            <div className="absolute inset-0 flex items-center justify-center pointer-events-none z-10 p-4">
              <div className="p-6 rounded-2xl bg-white border border-[#e8eaec] shadow-xs text-center space-y-3 pointer-events-auto max-w-sm">
                <div className="w-10 h-10 rounded-xl bg-[#dcfae5] text-[#2f7a45] border border-[#bbf7d0] flex items-center justify-center mx-auto">
                  <Play className="w-4 h-4 fill-current" />
                </div>
                <div>
                  <h4 className="text-sm font-bold text-[#131416]">Canvas is Empty</h4>
                  <p className="text-xs text-[#5d5f60] mt-1">
                    Click below to add a Start Step, or drag any step from the Node Library on the left.
                  </p>
                </div>
                <button
                  type="button"
                  onClick={() => handleAddNode('start', undefined, { x: 120, y: 220 })}
                  className="px-4 py-2 rounded-xl bg-[#1d5fd6] text-white text-xs font-semibold hover:bg-[#1853bf] transition shadow-xs inline-flex items-center gap-1.5"
                >
                  <Plus className="w-3.5 h-3.5" />
                  <span>Add Start Step</span>
                </button>
              </div>
            </div>
          )}

          {/* 3. Contextual Quick-Add Menu (Rendered at drop location) */}
          {quickAdd && (
            <div
              className="fixed z-50 bg-white border border-[#cdced1] rounded-[12px] p-2.5 w-64 shadow-md /* taste-skill-ignore */"
              style={{
                left: Math.min(window.innerWidth - 280, Math.max(20, quickAdd.screenPos.x)),
                top: Math.min(window.innerHeight - 340, Math.max(70, quickAdd.screenPos.y)),
              }}
            >
              <div className="flex items-center justify-between pb-2 mb-1.5 border-b border-[#f0f1f2]">
                <span className="text-[11px] font-semibold text-[#131416] flex items-center gap-1.5">
                  <Plus className="w-3.5 h-3.5 text-[#1d5fd6]" />
                  <span>Connect Next Step</span>
                </span>
                <button
                  onClick={() => setQuickAdd(null)}
                  className="text-[#5d5f60] hover:text-[#131416]"
                >
                  <X className="w-3.5 h-3.5" />
                </button>
              </div>

              <div className="space-y-1 max-h-72 overflow-y-auto">
                {AVAILABLE_NODES.map((item, idx) => {
                  const Icon = item.icon;
                  return (
                    <button
                      key={idx}
                      type="button"
                      onClick={() => handleQuickAddSelect(item.type, item.subType)}
                      className="w-full text-left p-1.5 rounded-[6px] hover:bg-[#f4f6f8] transition-colors flex items-center gap-2 group"
                    >
                      <div
                        className={`w-6 h-6 rounded-[4px] ${item.bgColor} ${item.color} flex items-center justify-center shrink-0 border ${item.borderColor}`}
                      >
                        <Icon className="w-3 h-3" />
                      </div>
                      <div className="min-w-0 flex-1">
                        <span className="text-xs font-medium text-[#131416] group-hover:text-[#1b6c80] truncate block">
                          {item.title}
                        </span>
                      </div>
                      <ArrowRight className="w-3 h-3 text-[#cdced1] group-hover:text-[#1b6c80] opacity-0 group-hover:opacity-100 transition-opacity shrink-0" />
                    </button>
                  );
                })}
              </div>
            </div>
          )}
        </div>

        {/* 4. Right Property Inspector Drawer */}
        {selectedNode && (
          <NodeInspector
            node={selectedNode}
            onUpdate={handleUpdateNode}
            onDelete={handleDeleteNode}
            onClose={() => setSelectedNodeId(null)}
            allNodes={nodes}
            funnelMode={funnel?.funnel_mode || 'quiz'}
          />
        )}
      </div>
    </div>
  );
};

export const BuilderPage: React.FC = () => {
  return (
    <ReactFlowProvider>
      <BuilderInner />
    </ReactFlowProvider>
  );
};
