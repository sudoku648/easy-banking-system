import { describe, it, expect, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import FormField from '../../components/FormField';

describe('FormField', () => {
  it('should render input field with label', () => {
    render(
      <FormField
        label="Username"
        name="username"
        value=""
        onChange={vi.fn()}
      />
    );

    expect(screen.getByLabelText('Username')).toBeInTheDocument();
    expect(screen.getByRole('textbox')).toBeInTheDocument();
  });

  it('should render input with value', () => {
    render(
      <FormField
        label="Username"
        name="username"
        value="testuser"
        onChange={vi.fn()}
      />
    );

    expect(screen.getByDisplayValue('testuser')).toBeInTheDocument();
  });

  it('should call onChange when typing', async () => {
    const handleChange = vi.fn();
    const user = userEvent.setup();

    render(
      <FormField
        label="Username"
        name="username"
        value=""
        onChange={handleChange}
      />
    );

    const input = screen.getByRole('textbox');
    await user.type(input, 'test');

    expect(handleChange).toHaveBeenCalled();
  });

  it('should render select field with options', () => {
    const options = [
      { value: '', label: 'Select...' },
      { value: '1', label: 'Option 1' },
      { value: '2', label: 'Option 2' },
    ];

    render(
      <FormField
        label="Choose"
        type="select"
        name="choice"
        value=""
        onChange={vi.fn()}
        options={options}
      />
    );

    expect(screen.getByRole('combobox')).toBeInTheDocument();
    expect(screen.getByText('Option 1')).toBeInTheDocument();
    expect(screen.getByText('Option 2')).toBeInTheDocument();
  });

  it('should render textarea field', () => {
    render(
      <FormField
        label="Description"
        type="textarea"
        name="description"
        value=""
        onChange={vi.fn()}
      />
    );

    const textarea = screen.getByRole('textbox');
    expect(textarea.tagName).toBe('TEXTAREA');
  });

  it('should display error message', () => {
    render(
      <FormField
        label="Email"
        name="email"
        value=""
        onChange={vi.fn()}
        error="Invalid email"
      />
    );

    expect(screen.getByText('Invalid email')).toBeInTheDocument();
  });

  it('should add is-invalid class when error present', () => {
    render(
      <FormField
        label="Email"
        name="email"
        value=""
        onChange={vi.fn()}
        error="Invalid email"
      />
    );

    const input = screen.getByRole('textbox');
    expect(input).toHaveClass('is-invalid');
  });

  it('should render help text', () => {
    render(
      <FormField
        label="Password"
        type="password"
        name="password"
        value=""
        onChange={vi.fn()}
        helpText="Must be at least 8 characters"
      />
    );

    expect(screen.getByText('Must be at least 8 characters')).toBeInTheDocument();
  });

  it('should mark field as required', () => {
    render(
      <FormField
        label="Email"
        name="email"
        value=""
        onChange={vi.fn()}
        required={true}
      />
    );

    const input = screen.getByRole('textbox');
    expect(input).toBeRequired();
  });

  it('should render number input with step attribute', () => {
    render(
      <FormField
        label="Amount"
        type="number"
        name="amount"
        value=""
        onChange={vi.fn()}
      />
    );

    const input = screen.getByRole('spinbutton');
    expect(input).toHaveAttribute('step', '0.01');
  });

  it('should render placeholder text', () => {
    render(
      <FormField
        label="Search"
        name="search"
        value=""
        onChange={vi.fn()}
        placeholder="Enter search term..."
      />
    );

    expect(screen.getByPlaceholderText('Enter search term...')).toBeInTheDocument();
  });
});
