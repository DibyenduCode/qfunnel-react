import React, { useState, useEffect, useRef } from 'react';
import {
  Play,
  Pause,
  Lock,
  Unlock,
  User,
  Mail,
  Phone,
  Building,
  Check,
  ChevronRight,
  AlertCircle,
  Video as VideoIcon,
  RefreshCw,
  ExternalLink,
  Volume2,
  VolumeX,
} from 'lucide-react';
import { api } from '../../api/client';

interface VideoGateStepProps {
  step: any;
  sessionToken: string;
  isDark: boolean;
  accentColor: string;
  buttonRadius: string;
  onAdvance: (res: any) => void;
  onError: (msg: string) => void;
}

export const VideoGateStep: React.FC<VideoGateStepProps> = ({
  step,
  sessionToken,
  isDark,
  accentColor,
  buttonRadius,
  onAdvance,
  onError,
}) => {
  const vg = step.video_gate || step.config || {};
  const playbackMode = vg.playback_mode || (vg.gate_enabled === false ? 'standard' : 'gated');
  const isStandard = playbackMode === 'standard';
  const videoUrl = vg.video_url || '';
  const provider = (vg.video_provider || 'youtube').toLowerCase();
  const videoId = vg.video_id || extractVideoId(videoUrl, provider);
  const gateEnabled = !isStandard && vg.gate_enabled !== false;
  const gateSeconds = parseFloat(vg.gate_timestamp_seconds ?? step.gate_timestamp_seconds ?? 45);
  const allowSkip = Boolean(vg.allow_skip_gate);
  const headline = vg.video_headline || step.title || 'Watch Video Presentation';
  const endAction = vg.end_action || (vg.on_video_end === 'message' || vg.on_video_end === 'thank_you' ? 'thank_you' : 'next_step');
  const ctaReveal = vg.cta_reveal || 'always';
  const ctaDelaySeconds = parseFloat(vg.cta_delay_seconds ?? 15);
  const ctaButtonText = vg.cta_button_text || 'Continue to Next Step';

  // Form Fields for Turnstile Modal
  const fields =
    (Array.isArray(vg.form_fields) && vg.form_fields.length > 0 && vg.form_fields) ||
    (Array.isArray(step.form_fields) && step.form_fields.length > 0 && step.form_fields) ||
    (Array.isArray(step.fields) && step.fields.length > 0 && step.fields) || [
      { id: 1, field_key: 'name', label: 'Full Name', field_type: 'text', placeholder: 'Enter your name', is_required: 1 },
      { id: 2, field_key: 'email', label: 'Email Address', field_type: 'email', placeholder: 'Enter your email', is_required: 1 },
    ];

  const [isPlaying, setIsPlaying] = useState(false);
  const [currentTime, setCurrentTime] = useState(0);
  const [duration, setDuration] = useState(0);
  const [isUnlocked, setIsUnlocked] = useState(isStandard || Boolean(step.is_gate_unlocked) || !gateEnabled);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [formData, setFormData] = useState<Record<string, string>>({});
  const [formErrors, setFormErrors] = useState<Record<string, string>>({});
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isCompleted, setIsCompleted] = useState(false);
  const [advancing, setAdvancing] = useState(false);
  const [isMuted, setIsMuted] = useState(false);
  const [showUnmuteHint, setShowUnmuteHint] = useState(false);

  const playerRef = useRef<any>(null);
  const containerId = useRef(`video-player-${step.node_id || Date.now()}`);
  const hasReachedGateRef = useRef(false);
  const isUnlockedRef = useRef(isUnlocked);
  isUnlockedRef.current = isUnlocked;

  // Helper: Format seconds to MM:SS
  const formatTime = (secs: number) => {
    if (isNaN(secs) || secs < 0) return '00:00';
    const m = Math.floor(secs / 60);
    const s = Math.floor(secs % 60);
    return `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
  };

  function extractVideoId(url: string, prov: string): string {
    if (!url) return '';
    if (prov === 'vimeo') {
      const match = url.match(/(?:vimeo\.com\/(?:video\/)?|player\.vimeo\.com\/video\/)([0-9]+)/i);
      return match ? match[1] : '';
    }
    const match = url.match(/(?:youtube\.com\/(?:watch\?(?:.*&)?v=|shorts\/|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/i);
    return match ? match[1] : '';
  }

  // Load YouTube / Vimeo SDKs
  useEffect(() => {
    let checkInterval: any = null;

    if (provider === 'youtube') {
      const loadYouTube = () => {
        if (!(window as any).YT || !(window as any).YT.Player) {
          if (!document.getElementById('yt-iframe-api')) {
            const tag = document.createElement('script');
            tag.id = 'yt-iframe-api';
            tag.src = 'https://www.youtube.com/iframe_api';
            document.body.appendChild(tag);
          }
          (window as any).onYouTubeIframeAPIReady = () => initYouTubePlayer();
        } else {
          initYouTubePlayer();
        }
      };

      const initYouTubePlayer = () => {
        if (!videoId || playerRef.current) return;
        try {
          playerRef.current = new (window as any).YT.Player(containerId.current, {
            videoId,
            playerVars: {
              autoplay: 1,
              controls: 0,
              disablekb: 1,
              fs: 0,
              modestbranding: 1,
              rel: 0,
              playsinline: 1,
              iv_load_policy: 3,
              cc_load_policy: 0,
              origin: window.location.origin,
            },
            events: {
              onReady: (event: any) => {
                setDuration(event.target.getDuration() || 0);
                
                // Immediately autoplay
                try {
                  event.target.playVideo();
                  setIsPlaying(true);
                } catch (e) {
                  console.warn('Autoplay error:', e);
                }

                // Browser autoplay check (if unmuted playback is blocked by Chrome/Safari/Edge policy)
                setTimeout(() => {
                  try {
                    const state = event.target.getPlayerState();
                    // State 1 = playing. If not playing, mute to satisfy browser policy and play!
                    if (state !== 1) {
                      event.target.mute();
                      event.target.playVideo();
                      setIsMuted(true);
                      setShowUnmuteHint(true);
                      setIsPlaying(true);
                    }
                  } catch (e) {
                    try {
                      event.target.mute();
                      event.target.playVideo();
                      setIsMuted(true);
                      setShowUnmuteHint(true);
                    } catch {}
                  }
                }, 350);

                api.post('/api/quiz/video-event', {
                  session_token: sessionToken,
                  node_id: step.node_id,
                  event_type: 'video_started',
                  event_data: { provider: 'youtube', video_id: videoId },
                }).catch(() => {});
              },
              onStateChange: (event: any) => {
                const state = event.data;
                // 1 = playing, 2 = paused, 0 = ended
                if (state === 1) setIsPlaying(true);
                if (state === 2) setIsPlaying(false);
                if (state === 0) {
                  setIsPlaying(false);
                  handleVideoEnded();
                }
              },
            },
          });
        } catch (err) {
          console.warn('YouTube Player init error:', err);
        }
      };

      loadYouTube();

      // Polling playback time to enforce turnstile gate
      checkInterval = setInterval(() => {
        if (playerRef.current && typeof playerRef.current.getCurrentTime === 'function') {
          const current = playerRef.current.getCurrentTime();
          setCurrentTime(current);

          if (playerRef.current.getDuration) {
            const dur = playerRef.current.getDuration();
            if (dur && dur > 0) setDuration(dur);
          }

          if (
            gateEnabled &&
            !isUnlockedRef.current &&
            current >= gateSeconds
          ) {
            if (!hasReachedGateRef.current) {
              hasReachedGateRef.current = true;
              playerRef.current.pauseVideo();
              playerRef.current.seekTo(gateSeconds, true);
              setIsPlaying(false);
              setIsModalOpen(true);

              api.post('/api/quiz/video-event', {
                session_token: sessionToken,
                node_id: step.node_id,
                event_type: 'video_gate_reached',
                event_data: { timestamp: gateSeconds, video_id: videoId },
              }).catch(() => {});
            } else {
              // Clamp to gate if somehow scrubbing occurred past gate
              playerRef.current.seekTo(gateSeconds, true);
              playerRef.current.pauseVideo();
              setIsModalOpen(true);
            }
          }
        }
      }, 250);
    } else if (provider === 'vimeo') {
      const loadVimeo = () => {
        if (!(window as any).Vimeo) {
          if (!document.getElementById('vimeo-player-sdk')) {
            const tag = document.createElement('script');
            tag.id = 'vimeo-player-sdk';
            tag.src = 'https://player.vimeo.com/api/player.js';
            tag.onload = () => initVimeoPlayer();
            document.body.appendChild(tag);
          }
        } else {
          initVimeoPlayer();
        }
      };

      const initVimeoPlayer = () => {
        const el = document.getElementById(containerId.current);
        if (!el || !videoId || playerRef.current) return;
        try {
          const player = new (window as any).Vimeo.Player(el, {
            id: videoId,
            responsive: true,
            autoplay: true,
            controls: false,
          });
          playerRef.current = player;

          player.getDuration().then((d: number) => setDuration(d));

          player.on('play', () => setIsPlaying(true));
          player.on('pause', () => setIsPlaying(false));
          player.on('ended', () => {
            setIsPlaying(false);
            handleVideoEnded();
          });

          player.on('timeupdate', (data: { seconds: number; duration: number }) => {
            setCurrentTime(data.seconds);
            setDuration(data.duration);

            if (
              gateEnabled &&
              !isUnlockedRef.current &&
              data.seconds >= gateSeconds
            ) {
              if (!hasReachedGateRef.current) {
                hasReachedGateRef.current = true;
                player.pause();
                player.setCurrentTime(gateSeconds);
                setIsPlaying(false);
                setIsModalOpen(true);

                api.post('/api/quiz/video-event', {
                  session_token: sessionToken,
                  node_id: step.node_id,
                  event_type: 'video_gate_reached',
                  event_data: { timestamp: gateSeconds, video_id: videoId },
                }).catch(() => {});
              } else {
                player.setCurrentTime(gateSeconds);
                player.pause();
                setIsModalOpen(true);
              }
            }
          });

          api.post('/api/quiz/video-event', {
            session_token: sessionToken,
            node_id: step.node_id,
            event_type: 'video_started',
            event_data: { provider: 'vimeo', video_id: videoId },
          }).catch(() => {});
        } catch (err) {
          console.warn('Vimeo Player init error:', err);
        }
      };

      loadVimeo();
    }

    return () => {
      if (checkInterval) clearInterval(checkInterval);
      if (playerRef.current) {
        try {
          if (typeof playerRef.current.destroy === 'function') {
            playerRef.current.destroy();
          }
        } catch (e) {}
      }
    };
  }, [videoId, provider, step.node_id, gateSeconds]);

  const handleVideoEnded = async () => {
    try {
      await api.post('/api/quiz/video-event', {
        session_token: sessionToken,
        node_id: step.node_id,
        event_type: 'video_completed',
      });

      if (endAction === 'thank_you') {
        setIsCompleted(true);
      } else {
        handleAdvanceNextNode();
      }
    } catch (err) {
      console.warn('Error on video end event:', err);
    }
  };

  const handleAdvanceNextNode = async () => {
    try {
      setAdvancing(true);
      const res = await api.post('/api/quiz/video-advance', {
        session_token: sessionToken,
        node_id: step.node_id,
      });
      onAdvance(res);
    } catch (err: any) {
      onError(err.message || 'Failed to advance to next step.');
    } finally {
      setAdvancing(false);
    }
  };

  const unmuteAudio = () => {
    if (playerRef.current) {
      if (provider === 'youtube' && typeof playerRef.current.unMute === 'function') {
        playerRef.current.unMute();
      } else if (provider === 'vimeo' && typeof playerRef.current.setMuted === 'function') {
        playerRef.current.setMuted(false);
      }
    }
    setIsMuted(false);
    setShowUnmuteHint(false);
  };

  const toggleMute = () => {
    if (!playerRef.current) return;
    if (provider === 'youtube') {
      if (isMuted) {
        if (typeof playerRef.current.unMute === 'function') playerRef.current.unMute();
        setIsMuted(false);
        setShowUnmuteHint(false);
      } else {
        if (typeof playerRef.current.mute === 'function') playerRef.current.mute();
        setIsMuted(true);
      }
    } else if (provider === 'vimeo') {
      if (typeof playerRef.current.setMuted === 'function') {
        playerRef.current.setMuted(!isMuted);
        setIsMuted(!isMuted);
      }
    }
  };

  const handleScrubberClick = (e: React.MouseEvent<HTMLDivElement>) => {
    if (!duration || duration <= 0) return;
    const rect = e.currentTarget.getBoundingClientRect();
    const clickRatio = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
    const targetSeconds = clickRatio * duration;

    // Strict Gate Protection:
    if (!isUnlocked && targetSeconds >= gateSeconds) {
      // Cannot scrub past the gate!
      if (playerRef.current) {
        if (typeof playerRef.current.seekTo === 'function') {
          playerRef.current.seekTo(gateSeconds, true);
          playerRef.current.pauseVideo();
        } else if (typeof playerRef.current.setCurrentTime === 'function') {
          playerRef.current.setCurrentTime(gateSeconds);
          playerRef.current.pause();
        }
      }
      setIsPlaying(false);
      setIsModalOpen(true);
      return;
    }

    if (playerRef.current) {
      if (typeof playerRef.current.seekTo === 'function') {
        playerRef.current.seekTo(targetSeconds, true);
      } else if (typeof playerRef.current.setCurrentTime === 'function') {
        playerRef.current.setCurrentTime(targetSeconds);
      }
    }
  };

  const togglePlayPause = () => {
    // If turnstile gate modal is open and not unlocked, keep paused
    if (isModalOpen && !isUnlocked) return;

    if (playerRef.current) {
      if (provider === 'youtube') {
        if (isPlaying) {
          if (typeof playerRef.current.pauseVideo === 'function') {
            playerRef.current.pauseVideo();
          }
          setIsPlaying(false);
        } else {
          if (isMuted && typeof playerRef.current.unMute === 'function') {
            playerRef.current.unMute();
            setIsMuted(false);
            setShowUnmuteHint(false);
          }
          if (typeof playerRef.current.playVideo === 'function') {
            playerRef.current.playVideo();
          }
          setIsPlaying(true);
        }
      } else if (provider === 'vimeo') {
        if (isPlaying) {
          if (typeof playerRef.current.pause === 'function') {
            playerRef.current.pause();
          }
          setIsPlaying(false);
        } else {
          if (typeof playerRef.current.play === 'function') {
            playerRef.current.play();
          }
          setIsPlaying(true);
        }
      }
    } else {
      setIsPlaying(!isPlaying);
    }
  };

  const resumeVideo = () => {
    setIsModalOpen(false);
    if (playerRef.current) {
      if (provider === 'youtube' && typeof playerRef.current.playVideo === 'function') {
        playerRef.current.playVideo();
        setIsPlaying(true);
      } else if (provider === 'vimeo' && typeof playerRef.current.play === 'function') {
        playerRef.current.play();
        setIsPlaying(true);
      }
    }
  };

  const handleGateSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);
    setFormErrors({});

    try {
      const res = await api.post('/api/quiz/video-gate-submit', {
        session_token: sessionToken,
        node_id: step.node_id,
        ...formData,
      });

      if (res.success) {
        setIsUnlocked(true);
        isUnlockedRef.current = true;
        resumeVideo();
      }
    } catch (err: any) {
      if (err.errors) {
        setFormErrors(err.errors);
      } else {
        setFormErrors({ _general: err.message || 'Please verify your details and try again.' });
      }
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleSkipGate = async () => {
    setIsUnlocked(true);
    isUnlockedRef.current = true;
    try {
      await api.post('/api/quiz/video-event', {
        session_token: sessionToken,
        node_id: step.node_id,
        event_type: 'video_gate_skipped',
        event_data: { timestamp: gateSeconds },
      });
    } catch (e) {}
    resumeVideo();
  };

  // Completed State View (if end_action === 'thank_you')
  if (isCompleted) {
    return (
      <div className="py-8 text-center space-y-6 animate-fadeIn">
        <div className="w-16 h-16 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-500 flex items-center justify-center mx-auto shadow-sm">
          <Check className="w-8 h-8 stroke-[2.5]" />
        </div>
        <div className="space-y-2">
          <h2 className={`text-2xl font-bold ${isDark ? 'text-white' : 'text-[#131416]'}`}>
            {vg.success_heading || '🎉 Thank You for Watching!'}
          </h2>
          <p className={`text-sm max-w-md mx-auto ${isDark ? 'text-slate-400' : 'text-[#5d5f60]'}`}>
            {vg.success_message || 'Your submission has been received. Our team will follow up shortly.'}
          </p>
        </div>
        {vg.cta_button_text && (
          <button
            type="button"
            onClick={handleAdvanceNextNode}
            disabled={advancing}
            style={{ backgroundColor: accentColor }}
            className={`px-8 py-3 text-white font-semibold text-sm shadow-md hover:brightness-110 active:scale-[0.98] transition-all inline-flex items-center gap-2 ${buttonRadius}`}
          >
            {advancing ? (
              <RefreshCw className="w-4 h-4 animate-spin" />
            ) : (
              <>
                <span>{vg.cta_button_text}</span>
                <ChevronRight className="w-4 h-4" />
              </>
            )}
          </button>
        )}
      </div>
    );
  }

  return (
    <div className="space-y-5">
      {/* Video Headline */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 pb-1">
        <div>
          <h2 className={`text-xl font-bold leading-snug ${isDark ? 'text-white' : 'text-[#131416]'}`}>
            {headline}
          </h2>
          <p className={`text-xs mt-0.5 ${isDark ? 'text-slate-400' : 'text-[#5d5f60]'}`}>
            {isStandard ? 'Video Presentation' : 'Interactive Video Presentation'}
          </p>
        </div>
      </div>

      {/* Responsive Video Container with 16:9 Aspect Ratio */}
      <div className="relative rounded-2xl overflow-hidden bg-black shadow-2xl border border-black/20 aspect-video select-none group">
        {/* Scaled & Cropped Video Frame:
            -top-[14%] -left-[10%] w-[120%] h-[128%] completely pushes YouTube's top title bar,
            channel avatar, and bottom-right "Watch on YouTube" watermark logo OUTSIDE the bounding box,
            leaving 100% pure video content without any external YouTube links! */}
        <div className="absolute -top-[14%] -left-[10%] w-[120%] h-[128%] overflow-hidden pointer-events-none">
          <div id={containerId.current} className="w-full h-full pointer-events-none" />
        </div>

        {/* Fallback iFrame if SDK hasn't mounted */}
        {!playerRef.current && videoId && (
          <div className="absolute -top-[14%] -left-[10%] w-[120%] h-[128%] overflow-hidden pointer-events-none">
            <iframe
              src={
                provider === 'vimeo'
                  ? `https://player.vimeo.com/video/${videoId}?autoplay=1&controls=0&background=1`
                  : `https://www.youtube.com/embed/${videoId}?autoplay=1&controls=0&disablekb=1&fs=0&modestbranding=1&rel=0&iv_load_policy=3&playsinline=1&enablejsapi=1&origin=${encodeURIComponent(
                      window.location.origin
                    )}`
              }
              title={headline}
              className="w-full h-full border-0 pointer-events-none"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
            />
          </div>
        )}

        {/* ========================================================
            TRANSPARENT CLICK-SHIELD OVERLAY
            Prevents ANY mouse click, tap, or context menu from reaching YouTube!
            The user CANNOT click title, avatar, YouTube logo, or redirect out.
            Clicking toggles Play / Pause and un-mutes cleanly.
        ======================================================== */}
        <div
          className="absolute inset-0 z-20 cursor-pointer select-none"
          onContextMenu={(e) => e.preventDefault()}
          onClick={() => {
            if (isMuted) unmuteAudio();
            togglePlayPause();
          }}
        />

        {/* Big Centered Play Overlay when Paused */}
        {!isPlaying && !isModalOpen && (
          <div
            onClick={() => {
              if (isMuted) unmuteAudio();
              togglePlayPause();
            }}
            className="absolute inset-0 z-25 flex items-center justify-center bg-black/40 backdrop-blur-[2px] transition-all cursor-pointer group"
          >
            <div className="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-indigo-600/90 text-white flex items-center justify-center shadow-2xl transition-transform transform group-hover:scale-110 active:scale-95 border-2 border-white/30">
              <Play className="w-8 h-8 fill-white ml-1" />
            </div>
          </div>
        )}

        {/* Floating Tap-to-Unmute Banner if browser muted autoplay */}
        {showUnmuteHint && isMuted && (
          <button
            type="button"
            onClick={unmuteAudio}
            className="absolute top-4 left-1/2 -translate-x-1/2 z-30 px-4 py-2 rounded-full bg-slate-900/90 text-white border border-white/25 shadow-2xl backdrop-blur-md text-xs font-semibold flex items-center gap-2 cursor-pointer animate-bounce hover:scale-105 active:scale-95 transition-all"
          >
            <VolumeX className="w-4 h-4 text-amber-400 shrink-0" />
            <span>Sound is muted — Click anywhere for audio 🔊</span>
          </button>
        )}
      </div>

      {/* Custom Video Control & Timeline Bar */}
      <div className="space-y-2 pt-1">
        <div className="flex items-center justify-between text-xs font-mono">
          <div className="flex items-center gap-3">
            {/* Custom Play / Pause Button */}
            <button
              type="button"
              onClick={togglePlayPause}
              disabled={isModalOpen && !isUnlocked}
              className={`flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold font-sans border shadow-sm transition-all active:scale-95 ${
                isPlaying
                  ? 'bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 border-slate-200 dark:border-slate-700 hover:bg-slate-200 dark:hover:bg-slate-700'
                  : 'bg-indigo-600 text-white border-indigo-500 hover:bg-indigo-500 shadow-indigo-500/25'
              }`}
              title={isPlaying ? 'Pause video' : 'Play video'}
            >
              {isPlaying ? (
                <>
                  <Pause className="w-3.5 h-3.5 fill-current" />
                  <span>Pause</span>
                </>
              ) : (
                <>
                  <Play className="w-3.5 h-3.5 fill-current ml-0.5" />
                  <span>Play</span>
                </>
              )}
            </button>

            <div className="flex items-center gap-1.5 text-slate-300">
              <span className="font-semibold text-white">
                {formatTime(currentTime)}
              </span>
              <span className="text-slate-500">/</span>
              <span className="text-slate-400">{formatTime(duration)}</span>
            </div>
          </div>

          <div className="flex items-center gap-2.5">
            {/* Custom Audio Mute / Unmute Toggle */}
            <button
              type="button"
              onClick={toggleMute}
              className={`flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium border transition-all ${
                isMuted
                  ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/30'
                  : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-200 dark:hover:bg-slate-700'
              }`}
              title={isMuted ? 'Unmute Audio' : 'Mute Audio'}
            >
              {isMuted ? (
                <>
                  <VolumeX className="w-3.5 h-3.5" />
                  <span>Unmute</span>
                </>
              ) : (
                <>
                  <Volume2 className="w-3.5 h-3.5" />
                  <span>Mute</span>
                </>
              )}
            </button>

            {/* Turnstile Gate Status Pill */}
            {gateEnabled && (
              <div
                className={`inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium border shrink-0 ${
                  isUnlocked
                    ? 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20'
                    : 'bg-amber-500/10 text-amber-500 border-amber-500/20'
                }`}
              >
                {isUnlocked ? (
                  <>
                    <Unlock className="w-3.5 h-3.5" />
                    <span>Gate Unlocked</span>
                  </>
                ) : (
                  <>
                    <Lock className="w-3.5 h-3.5" />
                    <span>Gate at {formatTime(gateSeconds)}</span>
                  </>
                )}
              </div>
            )}
          </div>
        </div>

        {/* Custom Progress Scrubber Bar */}
        <div
          onClick={handleScrubberClick}
          className="relative w-full h-2.5 rounded-full bg-slate-200 dark:bg-slate-800 overflow-visible cursor-pointer"
          title={isUnlocked ? 'Click to jump to time' : 'Seeking forward is locked until gate is unlocked'}
        >
          {/* Watched Progress Fill */}
          <div
            className="h-full rounded-full transition-all duration-150"
            style={{
              width: `${duration > 0 ? (currentTime / duration) * 100 : 0}%`,
              backgroundColor: accentColor,
            }}
          />

          {/* Gate Marker Pin */}
          {gateEnabled && duration > 0 && (
            <button
              type="button"
              onClick={(e) => {
                e.stopPropagation();
                if (!isUnlocked) {
                  if (playerRef.current && typeof playerRef.current.pauseVideo === 'function') {
                    playerRef.current.pauseVideo();
                    playerRef.current.seekTo(gateSeconds, true);
                  }
                  setIsPlaying(false);
                  setIsModalOpen(true);
                }
              }}
              className={`absolute top-1/2 -translate-y-1/2 -translate-x-1/2 w-5 h-5 rounded-full border-2 border-white shadow-md flex items-center justify-center text-[10px] z-10 transition-transform hover:scale-125 cursor-pointer ${
                isUnlocked ? 'bg-emerald-500 text-white' : 'bg-amber-500 text-white animate-pulse'
              }`}
              style={{
                left: `${Math.min(100, Math.max(0, (gateSeconds / duration) * 100))}%`,
              }}
              title={`Turnstile Gate at ${formatTime(gateSeconds)} — Click to complete`}
            >
              {isUnlocked ? '🔓' : '🔒'}
            </button>
          )}

          {/* CTA Button Reveal Pin */}
          {!gateEnabled && ctaReveal === 'delayed' && duration > 0 && (
            <div
              className={`absolute top-1/2 -translate-y-1/2 -translate-x-1/2 w-4 h-4 rounded-full border-2 border-white shadow-sm flex items-center justify-center text-[8px] z-10 transition-transform hover:scale-125 ${
                currentTime >= ctaDelaySeconds ? 'bg-emerald-500 text-white' : 'bg-indigo-600 text-white'
              }`}
              style={{
                left: `${Math.min(100, Math.max(0, (ctaDelaySeconds / duration) * 100))}%`,
              }}
              title={`Continue button arrives at ${formatTime(ctaDelaySeconds)}`}
            >
              🎯
            </div>
          )}
        </div>
      </div>

      {/* Advance to next step / CTA Button */}
      {isStandard && ctaReveal === 'delayed' && currentTime < ctaDelaySeconds && (
        <div className="pt-2 flex justify-end">
          <span className="text-xs text-slate-400 flex items-center gap-1.5 py-1.5 px-3 rounded-full bg-slate-100 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
            <span className="w-2 h-2 rounded-full bg-amber-400 animate-ping" />
            Continue button unlocks in {formatTime(Math.max(1, Math.ceil(ctaDelaySeconds - currentTime)))}
          </span>
        </div>
      )}

      {((isStandard && (ctaReveal === 'always' || (ctaReveal === 'delayed' && currentTime >= ctaDelaySeconds) || (ctaReveal === 'on_end' && isCompleted))) || (!isStandard && isUnlocked && (ctaReveal !== 'delayed' || currentTime >= ctaDelaySeconds))) && (
        <div className="pt-2 flex justify-end animate-fadeIn">
          <button
            type="button"
            onClick={handleAdvanceNextNode}
            disabled={advancing}
            style={{ backgroundColor: accentColor }}
            className={`px-6 py-2.5 text-white font-semibold text-xs shadow-sm hover:brightness-110 active:scale-[0.98] transition-all flex items-center gap-1.5 ${buttonRadius}`}
          >
            {advancing ? (
              <RefreshCw className="w-3.5 h-3.5 animate-spin" />
            ) : (
              <>
                <span>{ctaButtonText}</span>
                <ChevronRight className="w-3.5 h-3.5" />
              </>
            )}
          </button>
        </div>
      )}

      {/* ========================================================
          FULL SCREEN GLASSMORPHISM MID-ROLL POP-UP GATE MODAL
          Positioned fixed inset-0 over the entire page with backdrop blur
      ======================================================== */}
      {isModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 bg-slate-950/85 backdrop-blur-md animate-fadeIn overflow-y-auto">
          <div
            className={`w-full max-w-lg p-6 sm:p-8 rounded-3xl border shadow-2xl space-y-5 my-auto ${
              isDark
                ? 'bg-slate-900 border-slate-700/80 text-white'
                : 'bg-white border-slate-200 text-[#131416]'
            }`}
          >
            {/* Modal Header */}
            <div className="text-center space-y-2">
              <div className="w-12 h-12 rounded-2xl bg-amber-500/15 text-amber-500 border border-amber-500/25 flex items-center justify-center mx-auto mb-1 shadow-sm">
                <Lock className="w-6 h-6 stroke-[2.5]" />
              </div>
              <h3 className={`text-xl font-bold tracking-tight ${isDark ? 'text-white' : 'text-[#131416]'}`}>
                {vg.gate_headline || '🔒 Complete this quick step to continue watching'}
              </h3>
              <p className={`text-xs sm:text-sm ${isDark ? 'text-slate-400' : 'text-[#5d5f60]'}`}>
                {vg.gate_subheadline || 'Enter your details below to unlock the rest of this presentation.'}
              </p>
            </div>

            {formErrors._general && (
              <div className="p-3 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-500 text-xs flex items-center gap-2">
                <AlertCircle className="w-4 h-4 shrink-0" />
                <span>{formErrors._general}</span>
              </div>
            )}

            {/* Dynamic Form Fields */}
            <form onSubmit={handleGateSubmit} className="space-y-3.5 pt-1">
              {fields.map((f: any, idx: number) => {
                const key = f.field_key || f.key || `field_${idx}`;
                const isReq = Boolean(f.is_required);
                const isEmail = f.field_type === 'email' || key === 'email';
                const isPhone = f.field_type === 'phone' || key === 'phone';
                const isNumber = f.field_type === 'number';
                const isConsent = f.field_type === 'consent';

                if (isConsent) {
                  return (
                    <label
                      key={key}
                      className={`flex items-start gap-2.5 p-3 rounded-xl border cursor-pointer transition-colors ${
                        isDark
                          ? 'bg-slate-800/60 border-slate-700 hover:border-slate-600'
                          : 'bg-slate-50 border-slate-200 hover:border-slate-300'
                      }`}
                    >
                      <input
                        type="checkbox"
                        required={isReq}
                        checked={Boolean(formData[key])}
                        onChange={(e) => setFormData({ ...formData, [key]: e.target.checked ? '1' : '' })}
                        className="mt-0.5 rounded text-[#1d5fd6] accent-[#1d5fd6]"
                      />
                      <span className={`text-xs ${isDark ? 'text-slate-300' : 'text-[#333]'}`}>
                        {f.label || 'I agree to the Terms & Privacy Policy'} {isReq && <span className="text-rose-500">*</span>}
                      </span>
                    </label>
                  );
                }

                return (
                  <div key={key} className="space-y-1">
                    <label className={`block text-xs font-semibold ${isDark ? 'text-slate-300' : 'text-[#333]'}`}>
                      {f.label || key} {isReq && <span className="text-rose-500">*</span>}
                    </label>
                    <div className="relative">
                      <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        {isEmail ? (
                          <Mail className="w-4 h-4" />
                        ) : isPhone ? (
                          <Phone className="w-4 h-4" />
                        ) : key === 'company' ? (
                          <Building className="w-4 h-4" />
                        ) : (
                          <User className="w-4 h-4" />
                        )}
                      </div>
                      <input
                        type={isEmail ? 'email' : isPhone ? 'tel' : isNumber ? 'number' : 'text'}
                        required={isReq}
                        placeholder={f.placeholder || `Enter ${f.label || key}`}
                        value={formData[key] || ''}
                        onChange={(e) => setFormData({ ...formData, [key]: e.target.value })}
                        className={`w-full pl-10 pr-3.5 py-2.5 rounded-xl text-xs font-medium border transition-colors outline-none ${
                          isDark
                            ? 'bg-slate-800/80 border-slate-700 text-white focus:border-indigo-400 focus:ring-1 focus:ring-indigo-400'
                            : 'bg-white border-[#e2e5e9] text-[#131416] focus:border-[#1d5fd6] focus:ring-1 focus:ring-[#1d5fd6]'
                        } ${formErrors[key] ? 'border-rose-500' : ''}`}
                      />
                    </div>
                    {formErrors[key] && (
                      <p className="text-[11px] text-rose-500">{formErrors[key]}</p>
                    )}
                  </div>
                );
              })}

              {/* Submit / Unlock Button */}
              <button
                type="submit"
                disabled={isSubmitting}
                style={{ backgroundColor: accentColor }}
                className={`w-full py-3 text-white font-semibold text-xs sm:text-sm shadow-md hover:brightness-110 active:scale-[0.99] transition-all flex items-center justify-center gap-2 mt-4 ${buttonRadius}`}
              >
                {isSubmitting ? (
                  <>
                    <RefreshCw className="w-4 h-4 animate-spin" />
                    <span>Verifying & Unlocking...</span>
                  </>
                ) : (
                  <>
                    <span>{vg.gate_button_text || '▶️ Unlock & Continue Video'}</span>
                  </>
                )}
              </button>
            </form>

            {/* Optional Skip Option */}
            {allowSkip && (
              <div className="text-center pt-1">
                <button
                  type="button"
                  onClick={handleSkipGate}
                  className={`text-xs hover:underline transition-colors ${
                    isDark ? 'text-slate-400 hover:text-slate-200' : 'text-[#687080] hover:text-[#131416]'
                  }`}
                >
                  Skip this step and continue watching →
                </button>
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  );
};
