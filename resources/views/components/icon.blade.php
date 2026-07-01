@props(['name', 'size' => 20, 'class' => ''])

<span {{ $attributes->merge(['class' => "inline-flex items-center justify-center $class"]) }}
      style="width: {{ $size }}px; height: {{ $size }}px;">
    @include('icons.'.$name)
</span>
