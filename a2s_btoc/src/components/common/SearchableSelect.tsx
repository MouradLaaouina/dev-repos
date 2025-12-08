import React, { useState } from 'react';
import { Combobox } from '@headlessui/react';
import { Check, ChevronDown, Search } from 'lucide-react';
import { clsx } from 'clsx';

interface SearchableSelectProps {
  options: string[];
  value: string;
  onChange: (value: string) => void;
  placeholder?: string;
  label?: string;
  error?: string;
}

export const SearchableSelect: React.FC<SearchableSelectProps> = ({
  options,
  value,
  onChange,
  placeholder = 'Sélectionner...',
  label,
  error
}) => {
  const [query, setQuery] = useState('');

  const filteredOptions = query === ''
    ? options
    : options.filter((option) =>
        option.toLowerCase().includes(query.toLowerCase())
      );

  return (
    <div className="relative">
      {label && <label className="label">{label}</label>}
      <Combobox value={value} onChange={onChange}>
        <div className="relative">
          <div className="relative w-full">
            <div className="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
              <Search className="h-5 w-5 text-gray-400" />
            </div>
            <Combobox.Input
              className={clsx(
                "input pl-10 pr-10",
                error && "border-danger-300 focus:border-danger-500 focus:ring-danger-500"
              )}
              displayValue={(val: string) => val}
              onChange={(event) => {
                setQuery(event.target.value);
              }}
              placeholder={placeholder}
            />
            <Combobox.Button className="absolute inset-y-0 right-0 flex items-center pr-2">
              <ChevronDown className="h-5 w-5 text-gray-400" aria-hidden="true" />
            </Combobox.Button>
          </div>
          <Combobox.Options className="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm">
            {filteredOptions.length === 0 && query !== '' ? (
              <div className="relative cursor-default select-none px-4 py-2 text-gray-700">
                Aucun résultat trouvé.
              </div>
            ) : (
              filteredOptions.map((option) => (
                <Combobox.Option
                  key={option}
                  className={({ active }) =>
                    clsx(
                      'relative cursor-default select-none py-2 pl-10 pr-4',
                      active ? 'bg-primary-600 text-white' : 'text-gray-900'
                    )
                  }
                  value={option}
                >
                  {({ selected, active }) => (
                    <>
                      <span className={clsx('block truncate', selected ? 'font-medium' : 'font-normal')}>
                        {option}
                      </span>
                      {selected ? (
                        <span
                          className={clsx(
                            'absolute inset-y-0 left-0 flex items-center pl-3',
                            active ? 'text-white' : 'text-primary-600'
                          )}
                        >
                          <Check className="h-5 w-5" aria-hidden="true" />
                        </span>
                      ) : null}
                    </>
                  )}
                </Combobox.Option>
              ))
            )}
          </Combobox.Options>
        </div>
      </Combobox>
      {error && <p className="mt-1 text-sm text-danger-600">{error}</p>}
    </div>
  );
};