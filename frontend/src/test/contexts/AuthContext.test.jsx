import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import { AuthProvider, useAuth } from '../../contexts/AuthContext';
import api from '../../api/client';

// Mock the API client
vi.mock('../../api/client', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
  },
}));

// Test component that uses AuthContext
const TestComponent = () => {
  const { user, loading } = useAuth();

  if (loading) return <div>Loading...</div>;
  if (user) return <div>User: {user.username}</div>;
  return <div>No user</div>;
};

const TestComponentWithActions = () => {
  const { user, login, logout } = useAuth();

  return (
    <div>
      {user ? <div>Logged in as: {user.username}</div> : <div>Not logged in</div>}
      <button onClick={() => login({ username: 'test', password: 'pass' })}>Login</button>
      <button onClick={logout}>Logout</button>
    </div>
  );
};

describe('AuthContext', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('should throw error when useAuth is used outside provider', () => {
    // Suppress console.error for this test
    const spy = vi.spyOn(console, 'error').mockImplementation(() => {});

    expect(() => {
      render(<TestComponent />);
    }).toThrow('useAuth must be used within AuthProvider');

    spy.mockRestore();
  });

  it('should show loading state initially', async () => {
    api.get.mockResolvedValueOnce({
      data: { data: { user: { username: 'testuser' } } },
    });

    render(
      <AuthProvider>
        <TestComponent />
      </AuthProvider>
    );

    expect(screen.getByText('Loading...')).toBeInTheDocument();
  });

  it('should load user on mount if authenticated', async () => {
    api.get.mockResolvedValueOnce({
      data: { data: { user: { username: 'testuser', role: 'CUSTOMER' } } },
    });

    render(
      <AuthProvider>
        <TestComponent />
      </AuthProvider>
    );

    await waitFor(() => {
      expect(screen.getByText('User: testuser')).toBeInTheDocument();
    });

    expect(api.get).toHaveBeenCalledWith('/auth/me');
  });

  it('should show no user when not authenticated', async () => {
    api.get.mockRejectedValueOnce(new Error('Unauthorized'));

    render(
      <AuthProvider>
        <TestComponent />
      </AuthProvider>
    );

    await waitFor(() => {
      expect(screen.getByText('No user')).toBeInTheDocument();
    });
  });

  it('should login successfully', async () => {
    api.get.mockRejectedValueOnce(new Error('Not authenticated'));
    api.post.mockResolvedValueOnce({
      data: { data: { user: { username: 'testuser', role: 'CUSTOMER' } } },
    });

    render(
      <AuthProvider>
        <TestComponentWithActions />
      </AuthProvider>
    );

    await waitFor(() => {
      expect(screen.getByText('Not logged in')).toBeInTheDocument();
    });

    const loginButton = screen.getByRole('button', { name: 'Login' });
    loginButton.click();

    await waitFor(() => {
      expect(screen.getByText('Logged in as: testuser')).toBeInTheDocument();
    });

    expect(api.post).toHaveBeenCalledWith('/auth/login', {
      username: 'test',
      password: 'pass',
    });
  });

  it('should logout successfully', async () => {
    api.get.mockResolvedValueOnce({
      data: { data: { user: { username: 'testuser', role: 'CUSTOMER' } } },
    });
    api.post.mockResolvedValueOnce({});

    render(
      <AuthProvider>
        <TestComponentWithActions />
      </AuthProvider>
    );

    await waitFor(() => {
      expect(screen.getByText('Logged in as: testuser')).toBeInTheDocument();
    });

    const logoutButton = screen.getByRole('button', { name: 'Logout' });
    logoutButton.click();

    await waitFor(() => {
      expect(screen.getByText('Not logged in')).toBeInTheDocument();
    });

    expect(api.post).toHaveBeenCalledWith('/auth/logout');
  });
});
