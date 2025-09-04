import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mountWithDependencies } from './helpers/test-utils'
import App from '../App.vue'

// Mock the API module to prevent real HTTP calls
vi.mock('@/services/api', () => ({
  authApi: {
    login: vi.fn(),
    logout: vi.fn(),
    getUser: vi.fn(),
    updateProfile: vi.fn(),
    updatePassword: vi.fn(),
  }
}))

describe('App', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders without errors', async () => {
    const wrapper = await mountWithDependencies(App, {
      route: '/',
      stubs: {
        DefaultLayout: { 
          template: '<div class="default-layout-stub"><slot /></div>' 
        },
        AppNavigation: { template: '<nav>Navigation</nav>' },
        AppFooter: { template: '<footer>Footer</footer>' }
      }
    })

    expect(wrapper.find('#app').exists()).toBe(true)
    expect(wrapper.find('.default-layout-stub').exists()).toBe(true)
    expect(wrapper.find('.router-view-stub').exists()).toBe(true)
  })

  it('renders auth page layout when on login route', async () => {
    const wrapper = await mountWithDependencies(App, {
      route: '/login',
      stubs: {
        DefaultLayout: { 
          template: '<div class="default-layout-stub"><slot /></div>' 
        }
      }
    })

    expect(wrapper.find('#app').exists()).toBe(true)
    // When on login route, should NOT use DefaultLayout
    expect(wrapper.find('.default-layout-stub').exists()).toBe(false)
    // Should render RouterView directly
    expect(wrapper.find('.router-view-stub').exists()).toBe(true)
  })

  it('initializes auth store on mount', async () => {
    const wrapper = await mountWithDependencies(App, {
      route: '/',
      stubs: {
        DefaultLayout: { 
          template: '<div class="default-layout-stub"><slot /></div>' 
        }
      }
    })

    // The auth store should be initialized
    const authStore = wrapper.vm.$pinia.state.value.auth
    expect(authStore).toBeDefined()
  })
})
