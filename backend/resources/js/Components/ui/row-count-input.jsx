import React from 'react';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';

const RowCountInput = ({ value, onChange, ...props }) => {
  return (
    <div className="grid w-full max-w-sm items-center gap-1.5">
      <Label htmlFor="row-count">Number of Rows</Label>
      <Input
        type="number"
        id="row-count"
        placeholder="1000"
        value={value}
        onChange={onChange}
        min="1"
        {...props}
      />
    </div>
  );
};

export default RowCountInput;