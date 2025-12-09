{{/*
Expand the name of the chart.
*/}}
{{- define "app038.name" -}}
{{- default .Chart.Name .Values.nameOverride | trunc 63 | trimSuffix "-" }}
{{- end }}

{{/*
Create a default fully qualified app name.
*/}}
{{- define "app038.fullname" -}}
{{- if .Values.fullnameOverride }}
{{- .Values.fullnameOverride | trunc 63 | trimSuffix "-" }}
{{- else }}
{{- $name := default .Chart.Name .Values.nameOverride }}
{{- if contains $name .Release.Name }}
{{- .Release.Name | trunc 63 | trimSuffix "-" }}
{{- else }}
{{- printf "%s-%s" .Release.Name $name | trunc 63 | trimSuffix "-" }}
{{- end }}
{{- end }}
{{- end }}

{{/*
Create chart name and version as used by the chart label.
*/}}
{{- define "app038.chart" -}}
{{- printf "%s-%s" .Chart.Name .Chart.Version | replace "+" "_" | trunc 63 | trimSuffix "-" }}
{{- end }}

{{/*
Common labels
*/}}
{{- define "app038.labels" -}}
helm.sh/chart: {{ include "app038.chart" . }}
{{ include "app038.selectorLabels" . }}
{{- if .Chart.AppVersion }}
app.kubernetes.io/version: {{ .Chart.AppVersion | quote }}
{{- end }}
app.kubernetes.io/managed-by: {{ .Release.Service }}
{{- end }}

{{/*
Selector labels
*/}}
{{- define "app038.selectorLabels" -}}
app.kubernetes.io/name: {{ include "app038.name" . }}
app.kubernetes.io/instance: {{ .Release.Name }}
{{- end }}

{{/*
Laravel labels
*/}}
{{- define "app038.laravel.labels" -}}
{{ include "app038.labels" . }}
app.kubernetes.io/component: laravel
{{- end }}

{{/*
Laravel selector labels
*/}}
{{- define "app038.laravel.selectorLabels" -}}
{{ include "app038.selectorLabels" . }}
app.kubernetes.io/component: laravel
{{- end }}

{{/*
Svelte labels
*/}}
{{- define "app038.svelte.labels" -}}
{{ include "app038.labels" . }}
app.kubernetes.io/component: svelte
{{- end }}

{{/*
Svelte selector labels
*/}}
{{- define "app038.svelte.selectorLabels" -}}
{{ include "app038.selectorLabels" . }}
app.kubernetes.io/component: svelte
{{- end }}

{{/*
Redis labels
*/}}
{{- define "app038.redis.labels" -}}
{{ include "app038.labels" . }}
app.kubernetes.io/component: redis
{{- end }}

{{/*
Redis selector labels
*/}}
{{- define "app038.redis.selectorLabels" -}}
{{ include "app038.selectorLabels" . }}
app.kubernetes.io/component: redis
{{- end }}

{{/*
RabbitMQ labels
*/}}
{{- define "app038.rabbitmq.labels" -}}
{{ include "app038.labels" . }}
app.kubernetes.io/component: rabbitmq
{{- end }}

{{/*
RabbitMQ selector labels
*/}}
{{- define "app038.rabbitmq.selectorLabels" -}}
{{ include "app038.selectorLabels" . }}
app.kubernetes.io/component: rabbitmq
{{- end }}

{{/*
Create the name of the service account to use
*/}}
{{- define "app038.serviceAccountName" -}}
{{- if .Values.laravel.serviceAccount.create }}
{{- default (include "app038.fullname" .) .Values.laravel.serviceAccount.name }}
{{- else }}
{{- default "default" .Values.laravel.serviceAccount.name }}
{{- end }}
{{- end }}

{{/*
Image repository
*/}}
{{- define "app038.imageRepository" -}}
{{- if .Values.global.imageRegistry }}
{{- printf "%s/%s" .Values.global.imageRegistry .Values.laravel.image.repository }}
{{- else }}
{{- .Values.laravel.image.repository }}
{{- end }}
{{- end }}

{{/*
Svelte image repository
*/}}
{{- define "app038.svelte.imageRepository" -}}
{{- if .Values.global.imageRegistry }}
{{- printf "%s/%s" .Values.global.imageRegistry .Values.svelte.image.repository }}
{{- else }}
{{- .Values.svelte.image.repository }}
{{- end }}
{{- end }}

