type PredictionInputProps = {
  readonly label: string;
  readonly value: string;
  readonly onChange: (value: string) => void;
  readonly disabled?: boolean;
};

export function PredictionInput({
  label,
  value,
  onChange,
  disabled = false,
}: PredictionInputProps) {
  return (
    <label className="prediction-input">
      <span>{label}</span>
      <input
        aria-label={label}
        disabled={disabled}
        inputMode="numeric"
        min="0"
        pattern="[0-9]*"
        type="number"
        value={value}
        onChange={(event) => onChange(event.target.value)}
      />
    </label>
  );
}
