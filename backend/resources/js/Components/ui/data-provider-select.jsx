import React from 'react';
import { Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue } from '@/Components/ui/select';

const DataProviderSelect = ({ dataProviders, value, onChange }) => {
  return (
    <Select value={value} onValueChange={onChange}>
      <SelectTrigger>
        <SelectValue placeholder="Select a data provider" />
      </SelectTrigger>
      <SelectContent>
        {Object.entries(dataProviders).map(([group, providers]) => (
          <SelectGroup key={group}>
            <SelectLabel>{group}</SelectLabel>
            {providers.map((provider) => (
              <SelectItem key={provider} value={`${group}.${provider}`}>
                {provider}
              </SelectItem>
            ))}
          </SelectGroup>
        ))}
      </SelectContent>
    </Select>
  );
};

export default DataProviderSelect;