import { create } from 'zustand';

export interface TourStep {
  id: string;
  stepNumber: number;
  totalSteps: number;
  badge: string;
  title: string;
  description: string;
  targetSelector?: string;
  position?: 'top' | 'bottom' | 'left' | 'right' | 'center';
  diagramType: 'create' | 'details' | 'nodes_overview' | 'start_to_form' | 'form_to_decision_quiz' | 'publish_launch';
  actionPrompt?: string;
}

interface TourState {
  isOpen: boolean;
  currentStep: number;
  totalSteps: number;
  hasCompleted: boolean;
  startTour: (startStep?: number) => void;
  nextStep: () => void;
  prevStep: () => void;
  goToStep: (step: number) => void;
  skipTour: () => void;
  resetTour: () => void;
  checkAutoStart: () => void;
}

const STORAGE_KEY = 'qfunnel_onboarding_tour_completed';

export const useTourStore = create<TourState>((set, get) => ({
  isOpen: false,
  currentStep: 0,
  totalSteps: 6,
  hasCompleted: typeof window !== 'undefined' ? localStorage.getItem(STORAGE_KEY) === 'true' : false,

  startTour: (startStep = 0) => {
    set({ isOpen: true, currentStep: startStep });
  },

  nextStep: () => {
    const { currentStep, totalSteps } = get();
    if (currentStep < totalSteps - 1) {
      set({ currentStep: currentStep + 1 });
    } else {
      get().skipTour();
    }
  },

  prevStep: () => {
    const { currentStep } = get();
    if (currentStep > 0) {
      set({ currentStep: currentStep - 1 });
    }
  },

  goToStep: (step: number) => {
    const { totalSteps } = get();
    if (step >= 0 && step < totalSteps) {
      set({ currentStep: step });
    }
  },

  skipTour: () => {
    if (typeof window !== 'undefined') {
      localStorage.setItem(STORAGE_KEY, 'true');
    }
    set({ isOpen: false, hasCompleted: true });
  },

  resetTour: () => {
    set({ isOpen: true, currentStep: 0 });
  },

  checkAutoStart: () => {
    if (typeof window === 'undefined') return;
    const completed = localStorage.getItem(STORAGE_KEY) === 'true';
    if (!completed && !get().isOpen) {
      // Delay slightly so UI renders smoothly
      setTimeout(() => {
        set({ isOpen: true, currentStep: 0 });
      }, 700);
    }
  },
}));
