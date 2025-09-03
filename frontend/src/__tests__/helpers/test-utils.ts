import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import { createRouter, createWebHistory } from 'vue-router'
import { vi } from 'vitest'

// Mock router for tests
export const createMockRouter = (initialRoute = '/') => {
  const router = createRouter({
    history: createWebHistory(),
    routes: [
      {
        path: '/',
        name: 'home',
        component: { template: '<div>Home</div>' },
      },
      {
        path: '/login',
        name: 'login',
        component: { template: '<div>Login</div>' },
      },
    ],
  })

  router.push(initialRoute)
  return router
}

// Mock localStorage for tests
export const mockLocalStorage = () => {
  const storage: Record<string, string> = {}

  return {
    getItem: vi.fn((key: string) => storage[key] || null),
    setItem: vi.fn((key: string, value: string) => {
      storage[key] = value
    }),
    removeItem: vi.fn((key: string) => {
      delete storage[key]
    }),
    clear: vi.fn(() => {
      Object.keys(storage).forEach((key) => delete storage[key])
    }),
  }
}

// Setup function for components that need Pinia and Router
export const mountWithDependencies = async (
  component: unknown,
  options: {
    route?: string
    pinia?: unknown
    mocks?: Record<string, unknown>
    props?: Record<string, unknown>
    stubs?: Record<string, unknown>
  } = {},
) => {
  const router = createMockRouter(options.route)

  const pinia =
    options.pinia ||
    createTestingPinia({
      createSpy: vi.fn,
      stubActions: false,
    })

  // Mock global objects
  Object.defineProperty(window, 'localStorage', {
    value: mockLocalStorage(),
    writable: true,
  })

  // Navigate to the specified route if provided
  if (options.route) {
    await router.push(options.route)
    await router.isReady()
  }

  return mount(component, {
    global: {
      plugins: [pinia, router],
      mocks: {
        ...options.mocks,
      },
      stubs: {
        RouterView: { template: '<div class="router-view-stub">Router View</div>' },
        ...options.stubs,
      },
    },
    props: options.props,
  })
}
