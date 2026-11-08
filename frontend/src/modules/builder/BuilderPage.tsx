import React, { useState, useEffect, useCallback, useMemo, useRef } from 'react';
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
import { ContentNode } from './nodes/ContentNode';
import { LoaderNode } from './nodes/LoaderNode';
import { BookingNode } from './nodes/BookingNode';
import { ConditionNode } from './nodes/ConditionNode';
import { NodeInspector } from './inspector/NodeInspector';
import { BuilderToolbar } from './toolbar/BuilderToolbar';
import { NodePalette, AVAILABLE_NODES } from './palette/NodePalette';
import {
  X,
  ArrowRight,
  Plus,
  Play,
  Keyboard,
  Command
} from 'lucide-react';

interface QuickAddState {
  fromNode: Node;
  fromHandleId: string | null;
  screenPos: { x: number; y: number };
  flowPos: { x: number; y: number };
}

const BuilderInner: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const { screenToFlowPosition, fitView, zoomIn, zoomOut } = useReactFlow();

  const [funnel, setFunnel] = useState<any>(null);
  const [nodes, setNodes, onNodesChange] = useNodesState<Node>([]);
  const [edges, setEdges, onEdgesChange] = useEdgesState<Edge>([]);
  const [selectedNodeId, setSelectedNodeId] = useState<string | null>(null);

  const [isPaletteOpen, setIsPaletteOpen] = useState(true);
  const [quickAdd, setQuickAdd] = useState<QuickAddState | null>(null);
  const [showShortcutsModal, setShowShortcutsModal] = useState(false);

  const [loading, setLoading] = useState(true);
  const [isDirty, setIsDirty] = useState(false);
  const [isSaving, setIsSaving] = useState(false);
  const [isJustSaved, setIsJustSaved] = useState(false);
  const [isPublishing, setIsPublishing] = useState(false);
  const [validationErrors, setValidationErrors] = useState<string[]>([]);
  const [bannerMsg, setBannerMsg] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

  // Canvas History Stack for Undo/Redo
  const [historyPast, setHistoryPast] = useState<Array<{ nodes: Node[]; edges: Edge[] }>>([]);
  const [historyFuture, setHistoryFuture] = useState<Array<{ nodes: Node[]; edges: Edge[] }>>([]);

  const nodesRef = useRef<Node[]>(nodes);
  const edgesRef = useRef<Edge[]>(edges);
  const isNudgingRef = useRef<boolean>(false);
  const nudgeTimerRef = useRef<any>(null);

  useEffect(() => {
    nodesRef.current = nodes;
  }, [nodes]);

  useEffect(() => {
    edgesRef.current = edges;
  }, [edges]);

  const pushHistory = useCallback(() => {
    setHistoryPast((past) => {
      const snapshot = {
        nodes: JSON.parse(JSON.stringify(nodesRef.current)),
        edges: JSON.parse(JSON.stringify(edgesRef.current)),
      };
      const updated = [...past, snapshot];
      if (updated.length > 30) updated.shift();
      return updated;
    });
    setHistoryFuture([]);
  }, []);

  const handleUndo = useCallback(() => {
    setHistoryPast((past) => {
      if (past.length === 0) return past;
      const previous = past[past.length - 1];
      const newPast = past.slice(0, -1);

      const currentSnapshot = {
        nodes: JSON.parse(JSON.stringify(nodesRef.current)),
        edges: JSON.parse(JSON.stringify(edgesRef.current)),
      };
      setHistoryFuture((future) => [currentSnapshot, ...future]);

      setNodes(previous.nodes);
      setEdges(previous.edges);
      setSelectedNodeId(null);
      setIsDirty(true);

      setBannerMsg({ type: 'success', text: 'Action undone.' });
      setTimeout(() => {
        setBannerMsg((c) => (c?.text === 'Action undone.' ? null : c));
      }, 1500);

      return newPast;
    });
  }, [setNodes, setEdges]);

  const handleRedo = useCallback(() => {
    setHistoryFuture((future) => {
      if (future.length === 0) return future;
      const next = future[0];
      const newFuture = future.slice(1);

      const currentSnapshot = {
        nodes: JSON.parse(JSON.stringify(nodesRef.current)),
        edges: JSON.parse(JSON.stringify(edgesRef.current)),
      };
      setHistoryPast((past) => [...past, currentSnapshot]);

      setNodes(next.nodes);
      setEdges(next.edges);
      setSelectedNodeId(null);
      setIsDirty(true);

      setBannerMsg({ type: 'success', text: 'Action redone.' });
      setTimeout(() => {
        setBannerMsg((c) => (c?.text === 'Action redone.' ? null : c));
      }, 1500);

      return newFuture;
    });
  }, [setNodes, setEdges]);

  // Define node types memoized for React Flow performance
  const nodeTypes = useMemo(
    () => ({
      start: StartNode,
      form: FormNode,
      question: QuestionNode,
      content: ContentNode,
      video_gate: VideoGateNode,
      destination: DestinationNode,
      loader: LoaderNode,
      booking: BookingNode,
      condition: ConditionNode,
    }),
    []
  );

  useEffect(() => {
    loadGraph();
  }, [id]);

  // Reset just-saved success indicator when user makes new edits
  useEffect(() => {
    if (isDirty) {
      setIsJustSaved(false);
    }
  }, [isDirty]);

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
            video_gate: (() => {
              const rawVg = n.video_gate || parsedConfig.video_gate || parsedConfig || {};
              const cleanVg = { ...(typeof rawVg === 'object' ? rawVg : {}) };
              delete (cleanVg as any).video_gate;
              const vgSeconds = Number(
                n.video_gate?.gate_timestamp_seconds ??
                parsedConfig.video_gate?.gate_timestamp_seconds ??
                parsedConfig.gate_timestamp_seconds ??
                45
              );
              const vgFormFields =
                n.video_gate?.form_fields ||
                parsedConfig.video_gate?.form_fields ||
                parsedConfig.form_fields ||
                n.fields ||
                [];
              cleanVg.gate_timestamp_seconds = vgSeconds;
              cleanVg.form_fields = vgFormFields;
              return cleanVg;
            })(),
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
        sourceHandle: e.source_handle || (e.source_option_id ? String(e.source_option_id) : 'output'),
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
      pushHistory();
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
    [setEdges, pushHistory]
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
      pushHistory();
      setNodes((nds) =>
        nds.map((n) => (n.id === updatedNode.id ? updatedNode : n))
      );
      setIsDirty(true);
    },
    [setNodes, pushHistory]
  );

  const handleDeleteNode = useCallback(
    (nodeId: string) => {
      pushHistory();
      setNodes((nds) => nds.filter((n) => n.id !== nodeId));
      setEdges((eds) => eds.filter((e) => e.source !== nodeId && e.target !== nodeId));
      setSelectedNodeId(null);
      setIsDirty(true);
    },
    [setNodes, setEdges, pushHistory]
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
      pushHistory();
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
      } else if (type === 'condition') {
        initialData.title = 'Conditional Router';
        initialData.condition = {
          condition_type: 'score',
          operator: '>=',
          value: '80',
          variable_key: 'Q1',
          match_label: 'Qualified Lead',
          default_label: 'Standard Route',
        };
        initialData.config = initialData.condition;
      } else if (type === 'content') {
        initialData.title = 'Key Insight & Overview';
        initialData.content = {
          headline: 'Did you know? 80% of organizations see higher ROI',
          body_text: 'Explore this visual breakdown of key metrics before reviewing your customized recommendations.',
          image_url: 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=800&auto=format&fit=crop',
          button_text: 'Continue to Next Step',
        };
        initialData.config = initialData.content;
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
    [nodes, funnel, setNodes, pushHistory]
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
    pushHistory();

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
      const startTime = Date.now();

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
            fields: d.fields || d.form?.fields || d.video_gate?.form_fields,
            ...(() => {
              const vgClean = {
                ...(d.config?.video_gate || {}),
                ...(d.video_gate || {}),
              };
              delete (vgClean as any).video_gate;
              const vgSeconds = Number(d.video_gate?.gate_timestamp_seconds ?? d.config?.gate_timestamp_seconds ?? 45);
              const vgFields = d.video_gate?.form_fields || d.config?.form_fields || d.fields || [];
              const vgCtaDelay = Number(d.video_gate?.cta_delay_seconds ?? d.config?.video_gate?.cta_delay_seconds ?? d.config?.cta_delay_seconds ?? 15);
              const vgCtaReveal = d.video_gate?.cta_reveal || d.config?.video_gate?.cta_reveal || d.config?.cta_reveal || 'always';
              const vgCtaBtn = d.video_gate?.cta_button_text || d.config?.video_gate?.cta_button_text || d.config?.cta_button_text || 'Continue to Next Step';
              const vgPlaybackMode = d.video_gate?.playback_mode || d.config?.video_gate?.playback_mode || d.config?.playback_mode || 'gated';

              vgClean.gate_timestamp_seconds = vgSeconds;
              vgClean.form_fields = vgFields;
              vgClean.playback_mode = vgPlaybackMode;
              vgClean.cta_reveal = vgCtaReveal;
              vgClean.cta_delay_seconds = vgCtaDelay;
              vgClean.cta_button_text = vgCtaBtn;

              const configClean = { ...(d.config || {}) };
              delete (configClean as any).video_gate;
              configClean.video_gate = vgClean;
              configClean.gate_timestamp_seconds = vgSeconds;
              configClean.form_fields = vgFields;
              configClean.playback_mode = vgPlaybackMode;
              configClean.cta_reveal = vgCtaReveal;
              configClean.cta_delay_seconds = vgCtaDelay;
              configClean.cta_button_text = vgCtaBtn;

              return {
                video_gate: vgClean,
                config: configClean,
              };
            })(),
            condition: d.condition || d.config?.condition,
            content: d.content || d.config?.content,
            loader: d.loader || d.config?.loader,
            booking: d.booking || d.config?.booking,
            media: d.media || d.config?.media,
          };
        }),
        edges: edges.map((e) => ({
          source_node_id: e.source,
          source_handle: e.sourceHandle || null,
          source_option_id: e.sourceHandle && !['output', 'match', 'default'].includes(e.sourceHandle) && !isNaN(Number(e.sourceHandle)) ? e.sourceHandle : null,
          target_node_id: e.target,
        })),
      };

      await api.post(`/api/funnels/${id}/graph`, payload);

      // Ensure minimum 400ms duration so spinner is clearly perceptible & feels reassuring
      const elapsed = Date.now() - startTime;
      if (elapsed < 400) {
        await new Promise((resolve) => setTimeout(resolve, 400 - elapsed));
      }

      setIsDirty(false);
      setIsJustSaved(true);
      setBannerMsg({ type: 'success', text: 'Canvas saved successfully.' });

      setTimeout(() => {
        setIsJustSaved(false);
      }, 2600);

      setTimeout(() => {
        setBannerMsg((current) => (current?.text === 'Canvas saved successfully.' ? null : current));
      }, 2600);
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

  // Global Professional Keyboard Shortcuts (Delete, Nudge, Duplicate, Layout, Zoom, Fit)
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      const activeEl = document.activeElement;
      const isInput =
        activeEl &&
        (activeEl.tagName === 'INPUT' ||
          activeEl.tagName === 'TEXTAREA' ||
          activeEl.tagName === 'SELECT' ||
          (activeEl as HTMLElement).isContentEditable);

      // 1. Save Canvas: Ctrl+S or Cmd+S (Always intercepted globally)
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
        e.preventDefault();
        handleSaveGraph();
        return;
      }

      // If user is currently typing in an input/textarea, do NOT intercept node manipulation keys
      if (isInput) {
        if (e.key === 'Escape') {
          (activeEl as HTMLElement).blur();
        }
        return;
      }

      // 2. Escape: Close modals, deselect nodes/edges & cancel quickAdd
      if (e.key === 'Escape') {
        e.preventDefault();
        if (showShortcutsModal) {
          setShowShortcutsModal(false);
          return;
        }
        setSelectedNodeId(null);
        setQuickAdd(null);
        setNodes((nds) => nds.map((n) => ({ ...n, selected: false })));
        setEdges((eds) => eds.map((ed) => ({ ...ed, selected: false })));
        return;
      }

      // 3. Shortcuts Modal Toggle: ? or Shift + /
      if (e.key === '?' || (e.shiftKey && e.key === '/')) {
        e.preventDefault();
        setShowShortcutsModal((prev) => !prev);
        return;
      }

      // 4. Delete: Delete selected node(s) or selected edge(s) (Backspace removed as requested)
      if (e.key === 'Delete') {
        // A. Check for selected edges first
        const selectedEdges = edges.filter((ed) => ed.selected);
        if (selectedEdges.length > 0) {
          e.preventDefault();
          pushHistory();
          setEdges((eds) => eds.filter((ed) => !ed.selected));
          setIsDirty(true);
          return;
        }

        // B. Check for selected node(s)
        const targetNodeIds = new Set<string>();
        if (selectedNodeId) targetNodeIds.add(selectedNodeId);
        nodes.forEach((n) => {
          if (n.selected) targetNodeIds.add(n.id);
        });

        if (targetNodeIds.size > 0) {
          e.preventDefault();

          // Guard: Start Step cannot be deleted
          const nodesToDelete: string[] = [];
          let blockedStart = false;

          targetNodeIds.forEach((nId) => {
            const nodeObj = nodes.find((n) => n.id === nId);
            if (nodeObj?.type === 'start') {
              blockedStart = true;
            } else {
              nodesToDelete.push(nId);
            }
          });

          if (blockedStart && nodesToDelete.length === 0) {
            setBannerMsg({ type: 'error', text: 'Start Step cannot be deleted.' });
            setTimeout(() => setBannerMsg(null), 2500);
            return;
          }

          if (nodesToDelete.length > 0) {
            pushHistory();
            setNodes((nds) => nds.filter((n) => !nodesToDelete.includes(n.id)));
            setEdges((eds) =>
              eds.filter(
                (ed) => !nodesToDelete.includes(ed.source) && !nodesToDelete.includes(ed.target)
              )
            );
            setSelectedNodeId(null);
            setIsDirty(true);
            setBannerMsg({
              type: 'success',
              text: nodesToDelete.length === 1 ? 'Step deleted.' : `${nodesToDelete.length} steps deleted.`,
            });
            setTimeout(() => setBannerMsg(null), 2000);
          }
          return;
        }
      }

      // 5. Undo: Ctrl+Z (Windows) or Cmd+Z (Mac)
      if ((e.ctrlKey || e.metaKey) && !e.shiftKey && e.key.toLowerCase() === 'z') {
        e.preventDefault();
        handleUndo();
        return;
      }

      // 6. Redo: Ctrl+Y (Windows) or Cmd+Shift+Z / Cmd+Y (Mac)
      if (
        ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'y') ||
        ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key.toLowerCase() === 'z')
      ) {
        e.preventDefault();
        handleRedo();
        return;
      }

      // 7. Arrow Keys (Up, Down, Left, Right): Nudge selected node(s) with pixel precision
      const arrowKeys = ['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'];
      if (arrowKeys.includes(e.key)) {
        const hasSelection = selectedNodeId || nodes.some((n) => n.selected);
        if (hasSelection) {
          e.preventDefault();
          if (!isNudgingRef.current) {
            pushHistory();
            isNudgingRef.current = true;
          }
          if (nudgeTimerRef.current) clearTimeout(nudgeTimerRef.current);
          nudgeTimerRef.current = setTimeout(() => {
            isNudgingRef.current = false;
          }, 800);

          const step = e.shiftKey ? 40 : 10;
          let dx = 0;
          let dy = 0;

          if (e.key === 'ArrowUp') dy = -step;
          if (e.key === 'ArrowDown') dy = step;
          if (e.key === 'ArrowLeft') dx = -step;
          if (e.key === 'ArrowRight') dx = step;

          setNodes((nds) =>
            nds.map((n) => {
              if (n.id === selectedNodeId || n.selected) {
                return {
                  ...n,
                  position: {
                    x: Math.round(n.position.x + dx),
                    y: Math.round(n.position.y + dy),
                  },
                };
              }
              return n;
            })
          );
          setIsDirty(true);
          return;
        }
      }

      // 8. Duplicate Step: Ctrl+D or Cmd+D
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'd') {
        e.preventDefault();
        const activeNodeId = selectedNodeId || nodes.find((n) => n.selected)?.id;
        if (!activeNodeId) return;

        const sourceNode = nodes.find((n) => n.id === activeNodeId);
        if (!sourceNode) return;

        if (sourceNode.type === 'start') {
          setBannerMsg({ type: 'error', text: 'Cannot duplicate the Start Step.' });
          setTimeout(() => setBannerMsg(null), 2500);
          return;
        }

        pushHistory();
        const newId = `node_${Date.now()}`;
        const clonedData = JSON.parse(JSON.stringify(sourceNode.data || {}));
        if (clonedData.title) {
          clonedData.title = `${clonedData.title} (Copy)`;
        }
        if (Array.isArray(clonedData.options)) {
          clonedData.options = clonedData.options.map((opt: any, idx: number) => ({
            ...opt,
            id: `opt_${Date.now()}_${idx}`,
          }));
        }

        const newNode: Node = {
          ...sourceNode,
          id: newId,
          selected: true,
          position: {
            x: Math.round(sourceNode.position.x + 40),
            y: Math.round(sourceNode.position.y + 40),
          },
          data: clonedData,
        };

        setNodes((nds) => [
          ...nds.map((n) => ({ ...n, selected: false })),
          newNode,
        ]);
        setSelectedNodeId(newId);
        setIsDirty(true);
        setBannerMsg({ type: 'success', text: `Duplicated "${sourceNode.data?.title || 'Step'}".` });
        setTimeout(() => setBannerMsg(null), 2000);
        return;
      }

      // 7. Select All Nodes: Ctrl+A or Cmd+A
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'a') {
        e.preventDefault();
        setNodes((nds) => nds.map((n) => ({ ...n, selected: true })));
        return;
      }

      // 8. Auto-Arrange Tree Layout: L
      if (e.key.toLowerCase() === 'l' && !e.ctrlKey && !e.metaKey && !e.altKey) {
        e.preventDefault();
        handleAutoArrange();
        return;
      }

      // 9. Fit View: F
      if (e.key.toLowerCase() === 'f' && !e.ctrlKey && !e.metaKey && !e.altKey) {
        e.preventDefault();
        fitView({ duration: 300, padding: 0.2 });
        return;
      }

      // 10. Zoom In: + or =
      if ((e.key === '=' || e.key === '+') && !e.ctrlKey && !e.metaKey && !e.altKey) {
        e.preventDefault();
        zoomIn({ duration: 200 });
        return;
      }

      // 11. Zoom Out: -
      if (e.key === '-' && !e.ctrlKey && !e.metaKey && !e.altKey) {
        e.preventDefault();
        zoomOut({ duration: 200 });
        return;
      }
    };

    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [nodes, edges, selectedNodeId, isDirty, showShortcutsModal, fitView, zoomIn, zoomOut, handleUndo, handleRedo, pushHistory]);

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
        isJustSaved={isJustSaved}
        isPublishing={isPublishing}
        canUndo={historyPast.length > 0}
        canRedo={historyFuture.length > 0}
        onUndo={handleUndo}
        onRedo={handleRedo}
        funnelMode={funnel?.funnel_mode || 'quiz'}
        onModeChange={handleModeChange}
        onSave={handleSaveGraph}
        onPublish={handlePublish}
        onAddNode={handleAddNode}
        onAutoArrange={handleAutoArrange}
        isPaletteOpen={isPaletteOpen}
        onTogglePalette={() => setIsPaletteOpen((prev) => !prev)}
        onOpenShortcuts={() => setShowShortcutsModal(true)}
      />

      {/* Floating Canvas Notification Pill (Zero Layout Shift) */}
      {bannerMsg && (
        <div className="fixed top-14 left-1/2 -translate-x-1/2 z-50 pointer-events-none transition-all duration-300 ease-out">
          <div
            className={`flex items-center gap-2.5 px-4 py-2 rounded-full shadow-lg border text-xs font-medium backdrop-blur-md animate-in fade-in slide-in-from-top-3 duration-200 pointer-events-auto ${
              bannerMsg.type === 'success'
                ? 'bg-white/95 border-[#bbf0cc] text-[#04250e] shadow-[0_8px_24px_rgba(4,37,14,0.08)]'
                : 'bg-white/95 border-[#fad2cd] text-[#831c1e] shadow-[0_8px_24px_rgba(131,28,30,0.08)]'
            }`}
          >
            {bannerMsg.type === 'success' ? (
              <span className="w-4 h-4 rounded-full bg-[#dcfae5] text-[#04250e] flex items-center justify-center shrink-0">
                <svg className="w-3 h-3" viewBox="0 0 16 16" fill="none">
                  <path
                    d="M4.5 8.2L6.8 10.5L11.5 5.5"
                    stroke="#04250e"
                    strokeWidth="2"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    className="animate-check-stroke"
                  />
                </svg>
              </span>
            ) : (
              <span className="w-4 h-4 rounded-full bg-[#ffedea] text-[#831c1e] flex items-center justify-center shrink-0 font-bold text-[10px]">
                !
              </span>
            )}
            <span className="tracking-tight">{bannerMsg.text}</span>
          </div>
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
            onNodeDragStart={() => pushHistory()}
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
                if (n.type === 'condition') return '#c2410c';
                if (n.type === 'content') return '#1d5fd6';
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

      {/* 5. Keyboard Shortcuts Help Modal */}
      {showShortcutsModal && (
        <div
          className="fixed inset-0 bg-slate-900/40 backdrop-blur-xs z-50 flex items-center justify-center p-4 animate-in fade-in duration-150"
          onClick={() => setShowShortcutsModal(false)}
        >
          <div
            className="bg-white rounded-2xl border border-[#e8eaec] shadow-2xl max-w-md w-full overflow-hidden animate-in zoom-in-95 duration-200"
            onClick={(e) => e.stopPropagation()}
          >
            {/* Modal Header */}
            <div className="px-5 py-4 border-b border-[#e8eaec] flex items-center justify-between bg-[#f9fbfc]">
              <div className="flex items-center gap-2.5">
                <div className="w-8 h-8 rounded-lg bg-[#dcf1ff] text-[#0a3a80] flex items-center justify-center shrink-0">
                  <Keyboard className="w-4 h-4" />
                </div>
                <div>
                  <h3 className="text-sm font-semibold text-[#131416]">Keyboard Shortcuts</h3>
                  <p className="text-[11px] text-[#5d5f60]">Master canvas editing like a pro</p>
                </div>
              </div>
              <button
                type="button"
                onClick={() => setShowShortcutsModal(false)}
                className="p-1.5 rounded-lg text-[#5d5f60] hover:text-[#131416] hover:bg-[#eef1f4] transition"
                title="Close (Esc)"
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            {/* Shortcuts Content */}
            <div className="p-5 space-y-4 max-h-[70vh] overflow-y-auto text-xs">
              {/* Group: Editing */}
              <div className="space-y-2">
                <h4 className="text-[10px] font-bold uppercase tracking-wider text-[#5d5f60]">Step Manipulation</h4>
                <div className="space-y-2">
                  <div className="flex items-center justify-between py-1 border-b border-[#f4f6f8]">
                    <span className="text-[#131416]">Undo canvas action</span>
                    <div className="flex items-center gap-1">
                      <kbd className="px-1.5 py-0.5 rounded bg-[#f4f6f8] border border-[#e2e5e9] font-mono text-[10px] text-[#131416] shadow-2xs">Ctrl</kbd>
                      <span className="text-[10px]">+</span>
                      <kbd className="px-1.5 py-0.5 rounded bg-[#f4f6f8] border border-[#e2e5e9] font-mono text-[10px] text-[#131416] shadow-2xs">Z</kbd>
                      <span className="text-[#5d5f60] text-[10px] ml-1">/ ⌘Z</span>
                    </div>
                  </div>
                  <div className="flex items-center justify-between py-1 border-b border-[#f4f6f8]">
                    <span className="text-[#131416]">Redo canvas action</span>
                    <div className="flex items-center gap-1">
                      <kbd className="px-1.5 py-0.5 rounded bg-[#f4f6f8] border border-[#e2e5e9] font-mono text-[10px] text-[#131416] shadow-2xs">Ctrl</kbd>
                      <span className="text-[10px]">+</span>
                      <kbd className="px-1.5 py-0.5 rounded bg-[#f4f6f8] border border-[#e2e5e9] font-mono text-[10px] text-[#131416] shadow-2xs">Y</kbd>
                      <span className="text-[#5d5f60] text-[10px] ml-1">/ ⌘⇧Z</span>
                    </div>
                  </div>
                  <div className="flex items-center justify-between py-1 border-b border-[#f4f6f8]">
                    <span className="text-[#131416]">Delete selected step or connection</span>
                    <kbd className="px-2 py-0.5 rounded bg-[#f4f6f8] border border-[#e2e5e9] font-mono text-[10px] text-[#131416] shadow-2xs">Del</kbd>
                  </div>
                  <div className="flex items-center justify-between py-1 border-b border-[#f4f6f8]">
                    <span className="text-[#131416]">Nudge step position (10px)</span>
                    <div className="flex items-center gap-1">
                      <kbd className="px-1.5 py-0.5 rounded bg-[#f4f6f8] border border-[#e2e5e9] font-mono text-[10px] text-[#131416] shadow-2xs">↑</kbd>
                      <kbd className="px-1.5 py-0.5 rounded bg-[#f4f6f8] border border-[#e2e5e9] font-mono text-[10px] text-[#131416] shadow-2xs">↓</kbd>
                      <kbd className="px-1.5 py-0.5 rounded bg-[#f4f6f8] border border-[#e2e5e9] font-mono text-[10px] text-[#131416] shadow-2xs">←</kbd>
                      <kbd className="px-1.5 py-0.5 rounded bg-[#f4f6f8] border border-[#e2e5e9] font-mono text-[10px] text-[#131416] shadow-2xs">→</kbd>
                    </div>
                  </div>
                  <div className="flex items-center justify-between py-1 border-b border-[#f4f6f8]">
                    <span className="text-[#131416]">Fast nudge step (40px)</span>
                    <div className="flex items-center gap-1">
                      <kbd className="px-1.5 py-0.5 rounded bg-[#f4f6f8] border border-[#e2e5e9] font-mono text-[10px] text-[#131416] shadow-2xs">Shift</kbd>
                      <span className="text-[10px]">+</span>
                      <kbd className="px-1.5 py-0.5 rounded bg-[#f4f6f8] border border-[#e2e5e9] font-mono text-[10px] text-[#131416] shadow-2xs">Arrow</kbd>
                    </div>
                  </div>
                  <div className="flex items-center justify-between py-1 border-b border-[#f4f6f8]">
                    <span className="text-[#131416]">Duplicate selected step</span>
                    <div className="flex items-center gap-1">
                      <kbd className="px-1.5 py-0.5 rounded bg-[#f4f6f8] border border-[#e2e5e9] font-mono text-[10px] text-[#131416] shadow-2xs">Ctrl</kbd>
                      <span className="text-[10px]">+</span>
                      <kbd className="px-1.5 py-0.5 rounded bg-[#f4f6f8] border border-[#e2e5e9] font-mono text-[10px] text-[#131416] shadow-2xs">D</kbd>
                    </div>
                  </div>
                  <div className="flex items-center justify-between py-1 border-b border-[#f4f6f8]">
                    <span className="text-[#131416]">Save canvas changes</span>
                    <div className="flex items-center gap-1">
                      <kbd className="px-1.5 py-0.5 rounded bg-[#f4f6f8] border border-[#e2e5e9] font-mono text-[10px] text-[#131416] shadow-2xs">Ctrl</kbd>
                      <span className="text-[10px]">+</span>
                      <kbd className="px-1.5 py-0.5 rounded bg-[#f4f6f8] border border-[#e2e5e9] font-mono text-[10px] text-[#131416] shadow-2xs">S</kbd>
                    </div>
                  </div>
                  <div className="flex items-center justify-between py-1">
                    <span className="text-[#131416]">Select all steps</span>
                    <div className="flex items-center gap-1">
                      <kbd className="px-1.5 py-0.5 rounded bg-[#f4f6f8] border border-[#e2e5e9] font-mono text-[10px] text-[#131416] shadow-2xs">Ctrl</kbd>
                      <span className="text-[10px]">+</span>
                      <kbd className="px-1.5 py-0.5 rounded bg-[#f4f6f8] border border-[#e2e5e9] font-mono text-[10px] text-[#131416] shadow-2xs">A</kbd>
                    </div>
                  </div>
                </div>
              </div>

              {/* Group: Navigation & Canvas */}
              <div className="space-y-2 pt-2 border-t border-[#e8eaec]">
                <h4 className="text-[10px] font-bold uppercase tracking-wider text-[#5d5f60]">View & Navigation</h4>
                <div className="space-y-2">
                  <div className="flex items-center justify-between py-1 border-b border-[#f4f6f8]">
                    <span className="text-[#131416]">Auto-align clean tree layout</span>
                    <kbd className="px-1.5 py-0.5 rounded bg-[#f4f6f8] border border-[#e2e5e9] font-mono text-[10px] text-[#131416] shadow-2xs">L</kbd>
                  </div>
                  <div className="flex items-center justify-between py-1 border-b border-[#f4f6f8]">
                    <span className="text-[#131416]">Fit canvas to screen</span>
                    <kbd className="px-1.5 py-0.5 rounded bg-[#f4f6f8] border border-[#e2e5e9] font-mono text-[10px] text-[#131416] shadow-2xs">F</kbd>
                  </div>
                  <div className="flex items-center justify-between py-1 border-b border-[#f4f6f8]">
                    <span className="text-[#131416]">Zoom in / Zoom out</span>
                    <div className="flex items-center gap-1">
                      <kbd className="px-1.5 py-0.5 rounded bg-[#f4f6f8] border border-[#e2e5e9] font-mono text-[10px] text-[#131416] shadow-2xs">+</kbd>
                      <span className="text-[#5d5f60] text-[10px]">/</span>
                      <kbd className="px-1.5 py-0.5 rounded bg-[#f4f6f8] border border-[#e2e5e9] font-mono text-[10px] text-[#131416] shadow-2xs">-</kbd>
                    </div>
                  </div>
                  <div className="flex items-center justify-between py-1 border-b border-[#f4f6f8]">
                    <span className="text-[#131416]">Deselect step / Close panel</span>
                    <kbd className="px-1.5 py-0.5 rounded bg-[#f4f6f8] border border-[#e2e5e9] font-mono text-[10px] text-[#131416] shadow-2xs">Esc</kbd>
                  </div>
                  <div className="flex items-center justify-between py-1">
                    <span className="text-[#131416]">Toggle shortcuts guide</span>
                    <kbd className="px-1.5 py-0.5 rounded bg-[#f4f6f8] border border-[#e2e5e9] font-mono text-[10px] text-[#131416] shadow-2xs">?</kbd>
                  </div>
                </div>
              </div>
            </div>

            {/* Modal Footer */}
            <div className="px-5 py-3 border-t border-[#e8eaec] bg-[#f9fbfc] flex items-center justify-between">
              <span className="text-[11px] text-[#5d5f60]">Press <kbd className="px-1.5 py-0.5 rounded bg-white border border-[#e2e5e9] font-mono text-[10px]">Esc</kbd> to dismiss</span>
              <button
                type="button"
                onClick={() => setShowShortcutsModal(false)}
                className="px-3.5 py-1.5 rounded-lg bg-[#1d5fd6] text-white text-xs font-medium hover:bg-[#1853bf] transition shadow-xs"
              >
                Got it
              </button>
            </div>
          </div>
        </div>
      )}
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
