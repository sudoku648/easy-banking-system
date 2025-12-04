import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import Card from '../../components/Card';

describe('Card', () => {
  it('should render children', () => {
    render(
      <Card>
        <div>Test Content</div>
      </Card>
    );

    expect(screen.getByText('Test Content')).toBeInTheDocument();
  });

  it('should render title when provided', () => {
    render(
      <Card title="Card Title">
        <div>Content</div>
      </Card>
    );

    expect(screen.getByText('Card Title')).toBeInTheDocument();
  });

  it('should render with icon', () => {
    const { container } = render(
      <Card title="Card with Icon" icon="wallet2">
        <div>Content</div>
      </Card>
    );

    const icon = container.querySelector('.bi-wallet2');
    expect(icon).toBeInTheDocument();
  });

  it('should apply custom className', () => {
    const { container } = render(
      <Card className="custom-class">
        <div>Content</div>
      </Card>
    );

    const card = container.firstChild;
    expect(card).toHaveClass('custom-class');
  });

  it('should apply shadow-sm class by default', () => {
    const { container } = render(
      <Card>
        <div>Content</div>
      </Card>
    );

    const card = container.firstChild;
    expect(card).toHaveClass('card');
    expect(card).toHaveClass('shadow-sm');
  });

  it('should apply custom header background color', () => {
    const { container } = render(
      <Card title="Test" headerBg="success">
        <div>Content</div>
      </Card>
    );

    const header = container.querySelector('.card-header');
    expect(header).toHaveClass('bg-success');
  });

  it('should not render header when no title provided', () => {
    const { container } = render(
      <Card>
        <div>Content</div>
      </Card>
    );

    const header = container.querySelector('.card-header');
    expect(header).not.toBeInTheDocument();
  });
});
